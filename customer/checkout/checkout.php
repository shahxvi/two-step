<?php
require_once __DIR__ . '/../../config/authorization.php';
require_role(3);
require_once __DIR__ . '/../../config/db.php';

$user_id = $_SESSION['user_id'];
$total_price = 0;
$cart_data = [];
$display_data = [];
$customer_order_id = null;

// 1. Ambil data cart berdasarkan shoe_variant_id (mengelakkan isu shoe_id = 0)
$sql = "SELECT cart.quantity, shoe.shoe_name AS name, shoe.shoe_price AS price, 
               size.size_uk, color.color_name, cart.shoe_variant_id
        FROM cart 
        JOIN shoe_variant ON cart.shoe_variant_id = shoe_variant.shoe_variant_id
        JOIN shoe ON shoe_variant.shoe_id = shoe.shoe_id
        JOIN size ON shoe_variant.size_id = size.size_id
        JOIN color ON shoe_variant.color_id = color.color_id
        WHERE cart.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $cart_data[] = [
        'name'            => $row['name'],
        'price'           => $row['price'],
        'quantity'        => $row['quantity'],
        'size'            => $row['size_uk'],
        'color'           => $row['color_name'],
        'shoe_variant_id' => $row['shoe_variant_id']
    ];
}
$stmt->close();

// 2. Jika cart ada barang, masukkan ke customer_order dan order_details
if (!empty($cart_data)) {
    $status_id = 1;

    $insert_order = $conn->prepare("INSERT INTO customer_order (user_id, status_id, order_date_time) VALUES (?, ?, NOW())");
    if ($insert_order) {
        $insert_order->bind_param("ii", $user_id, $status_id);
        if ($insert_order->execute()) {
            $customer_order_id = $insert_order->insert_id;
        }
        $insert_order->close();
    }

    if ($customer_order_id) {
        $insert_detail = $conn->prepare("INSERT INTO order_details (customer_order_id, shoe_variant_id, quantity) VALUES (?, ?, ?)");
        if ($insert_detail) {
            foreach ($cart_data as $item) {
                $insert_detail->bind_param("iii", $customer_order_id, $item['shoe_variant_id'], $item['quantity']);
                $insert_detail->execute();
            }
            $insert_detail->close();
        }

        // Kosongkan cart selepas berjaya
        $clear_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        if ($clear_cart) {
            $clear_cart->bind_param("i", $user_id);
            $clear_cart->execute();
            $clear_cart->close();
        }
    }
} 

// 3. Jika cart kosong, ambil order terakhir user ini
if (!$customer_order_id) {
    $stmt_latest = $conn->prepare("SELECT customer_order_id FROM customer_order WHERE user_id = ? ORDER BY order_date_time DESC LIMIT 1");
    if ($stmt_latest) {
        $stmt_latest->bind_param("i", $user_id);
        $stmt_latest->execute();
        $res_latest = $stmt_latest->get_result();
        if ($row_latest = $res_latest->fetch_assoc()) {
            $customer_order_id = $row_latest['customer_order_id'];
        }
        $stmt_latest->close();
    }
}

// 4. Tarik data resit daripada database order_details
if ($customer_order_id) {
    $sql_receipt = "SELECT od.quantity, s.shoe_name AS name, s.shoe_price AS price, 
                           sz.size_uk, cl.color_name
                    FROM order_details od
                    JOIN shoe_variant sv ON od.shoe_variant_id = sv.shoe_variant_id
                    JOIN shoe s ON sv.shoe_id = s.shoe_id
                    JOIN size sz ON sv.size_id = sz.size_id
                    JOIN color cl ON sv.color_id = cl.color_id
                    WHERE od.customer_order_id = ?";

    $stmt_receipt = $conn->prepare($sql_receipt);
    if ($stmt_receipt) {
        $stmt_receipt->bind_param("i", $customer_order_id);
        $stmt_receipt->execute();
        $res_receipt = $stmt_receipt->get_result();

        while ($row_r = $res_receipt->fetch_assoc()) {
            $subtotal = $row_r['price'] * $row_r['quantity'];
            $total_price += $subtotal;
            
            $display_data[] = [
                'name'     => $row_r['name'],
                'price'    => $row_r['price'],
                'quantity' => $row_r['quantity'],
                'size'     => $row_r['size_uk'],
                'color'    => $row_r['color_name'],
                'subtotal' => $subtotal
            ];
        }
        $stmt_receipt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Step Sneakers: Receipt</title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png"/>
    <link rel="stylesheet" href="customer/checkout/checkout.css">
</head>
<body>
    <div class="logo-container">
        <div class="logo-box">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>
    </div>
    <main>
        <div class="receipt-container">
            <div class="receipt-head">
                <span class="paid-badge">ORDER RECEIPT</span>
            </div>
            <div class="receipt-paper">

                <div class="receipt-meta">
                    <div class="meta-box">
                        <h2><b>Details</b></h2>
                    </div>
                    <div class="meta-box"></div>
                </div>

                <?php if (!empty($display_data)): ?>
                <table class="receipt-table">
                    <tr>
                        <th>Item</th>
                        <th>Variant (Size / Color)</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                    <?php foreach ($display_data as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars('Size: ' . $item['size'] . ' | Color: ' . $item['color']); ?></td>
                            <td>RM<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>RM<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                    <p>No items found in this order.</p>
                <?php endif; ?>

                <div class="receipt-total">
                    <p><b>Total: RM<?php echo number_format($total_price, 2); ?></b></p>
                </div>

                <p class="thanks">Thank you for shopping with 2 Step Sneakers!</p>
            </div>

            <div class="receipt-actions no-print">
                <a href="customer/browse.php" class="receipt-btn">Continue Shopping</a>
                <a href="#" class="receipt-btn" onclick="window.print(); return false;">Print Receipt</a>
            </div>

        </div>
    </main>
</body>
</html>