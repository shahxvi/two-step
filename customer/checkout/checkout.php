<?php
require_once __DIR__ . '/../../config/authorization.php';
require_role(3);
require_once __DIR__ . '/../../config/db.php';

$userId = (int)$_SESSION['user_id'];
if (!isset($_SESSION['cart_csrf_token'])) {
    $_SESSION['cart_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['cart_csrf_token'];
$checkoutError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!is_string($action) || $action !== 'place_order') {
        http_response_code(400);
        exit('Invalid checkout action.');
    }

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (
        !is_string($submittedToken) ||
        !is_string($csrfToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        http_response_code(403);
        exit('Invalid form token.');
    }

    $transactionStarted = false;
    try {
        $conn->begin_transaction();
        $transactionStarted = true;

        $cartQuery = $conn->prepare(
            'SELECT c.cart_id, c.shoe_variant_id, c.quantity, sv.shoe_id,
                    sv.quantity AS stock_quantity, sv.status_id
             FROM cart AS c
             JOIN shoe_variant AS sv ON sv.shoe_variant_id = c.shoe_variant_id
             WHERE c.user_id = ?
             ORDER BY sv.shoe_variant_id, c.cart_id
             FOR UPDATE'
        );
        $cartQuery->bind_param('i', $userId);
        $cartQuery->execute();
        $cartRows = $cartQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        $cartQuery->close();

        if (!$cartRows) {
            $conn->rollback();
            header('Location: /two-step/customer/cart/cart.php?error=empty_cart');
            exit;
        }

        $itemsByVariant = [];
        foreach ($cartRows as $row) {
            $variantId = (int)$row['shoe_variant_id'];
            $quantity = (int)$row['quantity'];
            if ($quantity < 1) {
                throw new RuntimeException('The cart contains an invalid quantity.');
            }

            if (!isset($itemsByVariant[$variantId])) {
                $itemsByVariant[$variantId] = [
                    'shoe_variant_id' => $variantId,
                    'shoe_id' => (int)$row['shoe_id'],
                    'quantity' => 0,
                    'stock_quantity' => (int)$row['stock_quantity'],
                    'status_id' => (int)$row['status_id'],
                ];
            }
            $itemsByVariant[$variantId]['quantity'] += $quantity;
        }

        foreach ($itemsByVariant as $item) {
            if (
                $item['status_id'] !== 1 ||
                $item['stock_quantity'] < $item['quantity']
            ) {
                throw new RuntimeException('An item in your cart is no longer available in the requested quantity.');
            }
        }

        $orderStatusId = 1;
        $orderInsert = $conn->prepare(
            'INSERT INTO customer_order (user_id, status_id, order_date_time)
             VALUES (?, ?, NOW())'
        );
        $orderInsert->bind_param('ii', $userId, $orderStatusId);
        $orderInsert->execute();
        $orderId = (int)$orderInsert->insert_id;
        $orderInsert->close();

        $detailInsert = $conn->prepare(
            'INSERT INTO order_details (customer_order_id, shoe_variant_id, quantity)
             VALUES (?, ?, ?)'
        );
        $stockUpdate = $conn->prepare(
            'UPDATE shoe_variant
             SET quantity = quantity - ?
             WHERE shoe_variant_id = ? AND quantity >= ? AND status_id = 1'
        );
        $outOfStockUpdate = $conn->prepare(
            'UPDATE shoe_variant SET status_id = 2
             WHERE shoe_variant_id = ? AND quantity = 0 AND status_id = 1'
        );

        foreach ($itemsByVariant as $item) {
            $variantId = $item['shoe_variant_id'];
            $quantity = $item['quantity'];

            $detailInsert->bind_param('iii', $orderId, $variantId, $quantity);
            $detailInsert->execute();

            $stockUpdate->bind_param('iii', $quantity, $variantId, $quantity);
            $stockUpdate->execute();
            if ($stockUpdate->affected_rows !== 1) {
                throw new RuntimeException('Stock changed while placing your order.');
            }

            if ($item['stock_quantity'] === $quantity) {
                $outOfStockUpdate->bind_param('i', $variantId);
                $outOfStockUpdate->execute();
            }
        }

        $detailInsert->close();
        $stockUpdate->close();
        $outOfStockUpdate->close();

        $clearCart = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
        $clearCart->bind_param('i', $userId);
        $clearCart->execute();
        $clearCart->close();

        $conn->commit();
        $transactionStarted = false;
        $_SESSION['last_cart_order_id'] = $orderId;
        header('Location: /two-step/customer/checkout/checkout.php?complete=1');
        exit;
    } catch (RuntimeException $exception) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        header('Location: /two-step/customer/cart/cart.php?error=stock_changed');
        exit;
    } catch (mysqli_sql_exception $exception) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        http_response_code(500);
        $checkoutError = 'Unable to complete the order right now. Your cart has not been cleared; please try again.';
    }
}

$isReceipt = ($_GET['complete'] ?? '') === '1';
$receiptOrderId = null;
$receiptOrderDate = null;
$displayData = [];
$cartData = [];
$totalPrice = 0.0;

if ($isReceipt) {
    $sessionOrderId = filter_var(
        $_SESSION['last_cart_order_id'] ?? null,
        FILTER_VALIDATE_INT
    );
    if ($sessionOrderId !== false && $sessionOrderId !== null && $sessionOrderId > 0) {
        $orderQuery = $conn->prepare(
            'SELECT customer_order_id, order_date_time
             FROM customer_order
             WHERE customer_order_id = ? AND user_id = ?'
        );
        $orderQuery->bind_param('ii', $sessionOrderId, $userId);
        $orderQuery->execute();
        $order = $orderQuery->get_result()->fetch_assoc();
        $orderQuery->close();

        if ($order) {
            $receiptOrderId = (int)$order['customer_order_id'];
            $receiptOrderDate = $order['order_date_time'];

            $receiptQuery = $conn->prepare(
                'SELECT od.quantity, s.shoe_name AS name, sv.shoe_price AS price,
                        sz.size_uk, cl.color_name
                 FROM order_details AS od
                 JOIN shoe_variant AS sv ON od.shoe_variant_id = sv.shoe_variant_id
                 JOIN shoe AS s ON sv.shoe_id = s.shoe_id
                 JOIN size AS sz ON sv.size_id = sz.size_id
                 JOIN color AS cl ON sv.color_id = cl.color_id
                 WHERE od.customer_order_id = ?'
            );
            $receiptQuery->bind_param('i', $receiptOrderId);
            $receiptQuery->execute();
            $receiptRows = $receiptQuery->get_result();

            while ($row = $receiptRows->fetch_assoc()) {
                $price = (float)$row['price'];
                $quantity = (int)$row['quantity'];
                $subtotal = $price * $quantity;
                $totalPrice += $subtotal;
                $displayData[] = [
                    'name' => $row['name'],
                    'price' => $price,
                    'quantity' => $quantity,
                    'size' => $row['size_uk'],
                    'color' => $row['color_name'],
                    'subtotal' => $subtotal,
                ];
            }
            $receiptQuery->close();
        }
    }
} else {
    $cartQuery = $conn->prepare(
        'SELECT c.shoe_variant_id, SUM(c.quantity) AS quantity,
                s.shoe_name AS name, sv.shoe_price AS price,
                sz.size_uk, cl.color_name
         FROM cart AS c
         JOIN shoe_variant AS sv ON sv.shoe_variant_id = c.shoe_variant_id
         JOIN shoe AS s ON s.shoe_id = sv.shoe_id
         JOIN size AS sz ON sz.size_id = sv.size_id
         JOIN color AS cl ON cl.color_id = sv.color_id
         WHERE c.user_id = ?
         GROUP BY c.shoe_variant_id, s.shoe_name, sv.shoe_price, sz.size_uk, cl.color_name
         ORDER BY c.shoe_variant_id'
    );
    $cartQuery->bind_param('i', $userId);
    $cartQuery->execute();
    $cartRows = $cartQuery->get_result();

    while ($row = $cartRows->fetch_assoc()) {
        $price = (float)$row['price'];
        $quantity = (int)$row['quantity'];
        $subtotal = $price * $quantity;
        $totalPrice += $subtotal;
        $cartData[] = [
            'name' => $row['name'],
            'price' => $price,
            'quantity' => $quantity,
            'size' => $row['size_uk'],
            'color' => $row['color_name'],
            'subtotal' => $subtotal,
        ];
    }
    $cartQuery->close();
}

function checkout_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Step Sneakers: <?= $isReceipt ? 'Order Receipt' : 'Review Order' ?></title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="customer/checkout/checkout.css">
    <link rel="stylesheet" href="site-nav.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="customer/browse.php"><img src="images/logo.png" alt="2 Step Sneakers" class="logo"></a>
        </div>
        <nav>
            <a href="customer/browse.php"><strong>Browse</strong></a> |
            <a href="customer/cart/cart.php"><strong>Cart</strong></a> |
            <a href="auth/logout.php"><strong>Logout</strong></a>
        </nav>
    </header>
    <main>
        <div class="receipt-container">
            <div class="receipt-head">
                <span class="paid-badge"><?= $isReceipt ? 'ORDER RECEIPT' : 'REVIEW ORDER' ?></span>
            </div>
            <div class="receipt-paper">
                <?php if ($checkoutError !== ''): ?>
                    <p role="alert" style="color: #b91c1c;"><?= checkout_e($checkoutError) ?></p>
                <?php endif; ?>

                <?php if ($isReceipt && $receiptOrderId !== null): ?>
                    <h2>Order #ORD<?= str_pad((string)$receiptOrderId, 3, '0', STR_PAD_LEFT) ?></h2>
                    <p>Placed: <?= checkout_e(date('d M Y, h:i A', strtotime($receiptOrderDate))) ?></p>
                    <?php if ($displayData): ?>
                        <table class="receipt-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Variant (Size / Color)</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($displayData as $item): ?>
                                    <tr>
                                        <td><?= checkout_e($item['name']) ?></td>
                                        <td><?= checkout_e('Size: ' . $item['size'] . ' | Color: ' . $item['color']) ?></td>
                                        <td>RM<?= number_format($item['price'], 2) ?></td>
                                        <td><?= (int)$item['quantity'] ?></td>
                                        <td>RM<?= number_format($item['subtotal'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="receipt-total">
                            <p><b>Total: RM<?= number_format($totalPrice, 2) ?></b></p>
                        </div>
                        <p class="thanks">Thank you for shopping with 2 Step Sneakers!</p>
                    <?php else: ?>
                        <p>No items were found for this order.</p>
                    <?php endif; ?>
                    <div class="receipt-actions no-print">
                        <a href="customer/browse.php" class="receipt-btn">Continue Shopping</a>
                        <a href="#" class="receipt-btn" onclick="window.print(); return false;">Print Receipt</a>
                    </div>
                <?php elseif (!$isReceipt && $cartData): ?>
                    <p>Review your items before placing the order. Stock is confirmed when you place it.</p>
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Variant (Size / Color)</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartData as $item): ?>
                                <tr>
                                    <td><?= checkout_e($item['name']) ?></td>
                                    <td><?= checkout_e('Size: ' . $item['size'] . ' | Color: ' . $item['color']) ?></td>
                                    <td>RM<?= number_format($item['price'], 2) ?></td>
                                    <td><?= (int)$item['quantity'] ?></td>
                                    <td>RM<?= number_format($item['subtotal'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="receipt-total">
                        <p><b>Total: RM<?= number_format($totalPrice, 2) ?></b></p>
                    </div>
                    <form method="POST" class="receipt-actions no-print">
                        <input type="hidden" name="csrf_token" value="<?= checkout_e($csrfToken) ?>">
                        <input type="hidden" name="action" value="place_order">
                        <a href="customer/cart/cart.php" class="receipt-btn">Back to Cart</a>
                        <button type="submit" class="receipt-btn">Place Order</button>
                    </form>
                <?php else: ?>
                    <p><?= $isReceipt ? 'No recent order receipt is available.' : 'Your cart is empty.' ?></p>
                    <div class="receipt-actions no-print">
                        <a href="customer/browse.php" class="receipt-btn">Continue Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
