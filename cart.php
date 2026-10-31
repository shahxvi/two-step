<?php
include 'cart_logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Step Sneakers: Cart</title>
    <link rel="icon" type="image/jpg" href="images/logo.png"/>
    <link rel="stylesheet" href="cart.css">
</head>
<body>
    <div class="logo-container" style ="background-color: #a5998d;">
        <div style="background-color: white; padding: 5px 12px; border-radius: 4px; display: inline-block;">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
    </div>
    </div>
</body>

<main>
    <div class="cart-container">
        <h1 class="cart-title"><strong>Shopping Cart</strong></h1>
        <div class="cart-items">

            <?php if (!empty($cart_data)): ?>
                <?php foreach ($cart_data as $item): ?>
                    <div class="cart-item-row">
                        <div class="item-details">
                            <b>Name: <?php echo htmlspecialchars($item['name']); ?></b> |
                            <b>Price: RM<?php echo number_format($item['price'], 2); ?></b> | 
                            <b>Quantity: <?php echo $item['quantity']; ?></b> | 
                            <b>Subtotal: RM<?php echo number_format($item['subtotal'], 1); ?></b>
                        </div>
                        </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px;">Your cart is empty.</p>
            <?php endif; ?>
            </div>
        <div class="cart-summary">
            <p>Total: RM<?php echo number_format($total_price, 2); ?></p>
            <button type="submit" class="checkout-btn">Checkout</button>
        </div>
    </div>