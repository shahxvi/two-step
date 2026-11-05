<?php
include 'receipt.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Step Sneakers: Receipt</title>
    <link rel="icon" type="image/png" href="images/logo.png"/>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>

    <!-- logo -->
    <div class="logo-container">
        <div class="logo-box">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>
    </div>

    <main>
        <div class="receipt-container">
            <!-- Header -->
            <div class="receipt-head">
                <span class="paid-badge">ORDER RECEIPT</span>
            </div>

            <!-- Details -->
            <div class="receipt-paper">

                <div class="receipt-meta">
                    <div class="meta-box">
                        <h2><b>Details</b></h2>
                    </div>
                    <div class="meta-box">
                        
                    </div>
                </div>

                <?php if (!empty($cart_data)): ?>
    <table class="receipt-table">
        <tr>
            <th>Item</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
        </tr>
        <?php foreach ($cart_data as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
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
            <p><b>Total: RM<b><?php echo number_format($total_price, 2); ?></p>
                </div>

                <p class="thanks">Thank you for shopping with 2 Step Sneakers!</p>
            </div>

            <div class="receipt-actions no-print">
                <a href="browse.html" class="receipt-btn">Continue Shopping</a>
                <a href="#" class="receipt-btn" onclick="window.print(); return false;">Print Receipt</a>
            </div>

        </div>
    </main>
</body>
