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
    <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
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
                            Price: RM<?php echo number_format($item['price'], 2); ?> | 
                            Quantity: <?php echo $item['quantity']; ?> | 
                            <b>Subtotal: RM<?php echo number_format($item['subtotal'], 2); ?></b>
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