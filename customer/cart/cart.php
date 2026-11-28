<?php
include 'cart_logic.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Step Sneakers: Cart</title>
    <base href="/two-step/">
    <link rel="icon" type="image/jpg" href="images/logo.png"/>
    <link rel="stylesheet" href="customer/cart/cart.css">
    <link rel="stylesheet" href="site-nav.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>
        <nav>
            <a href="customer/browse.php"><strong>Browse</strong></a> |
            <a href="customer/cart/cart.php"><strong>Cart</strong></a> |
            <a href="auth/logout.php"><strong>Logout</strong></a>
        </nav>
    </header>

    <main>
        <section class="cart-container" aria-labelledby="cart-title">
            <div class="cart-heading">
                <div>
                    <h1 class="cart-title" id="cart-title">Shopping Cart</h1>
                </div>
                <?php if ($cart_data): ?>
                    <a class="continue-shopping-btn" href="customer/browse.php">Continue shopping</a>
                <?php endif; ?>
            </div>

            <?php if (($_GET['error'] ?? '') === 'stock_limit'): ?>
                <p class="cart-alert" role="alert">You cannot add more than the available stock.</p>
            <?php elseif (($_GET['error'] ?? '') === 'stock_changed'): ?>
                <p class="cart-alert" role="alert">An item in your cart is no longer available in the requested quantity. Please review your cart.</p>
            <?php elseif (($_GET['error'] ?? '') === 'empty_cart'): ?>
                <p class="cart-alert" role="alert">Your cart is empty.</p>
            <?php endif; ?>

            <?php if ($cart_data): ?>
                <div class="cart-items">
                    <?php foreach ($cart_data as $item): ?>
                        <article class="cart-item-row">
                            <div class="item-details">
                                <h2 class="item-name"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="item-variant">
                                    Size UK <?php echo htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span aria-hidden="true">&middot;</span>
                                    <?php echo htmlspecialchars($item['color'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <p class="item-price">RM<?php echo number_format($item['price'], 2); ?> each</p>

                                <div class="cart-item-actions">
                                    <div class="quantity-control" aria-label="Quantity controls">
                                        <form action="customer/cart/update_cart.php" method="POST" class="cart-action-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cart_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="minus">
                                            <input type="hidden" name="shoe_variant_id" value="<?php echo (int)$item['shoe_variant_id']; ?>">
                                            <button type="submit" class="quantity-btn" aria-label="Decrease quantity">&minus;</button>
                                        </form>
                                        <span class="quantity-value"><?php echo (int)$item['quantity']; ?></span>
                                        <form action="customer/cart/update_cart.php" method="POST" class="cart-action-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cart_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="shoe_variant_id" value="<?php echo (int)$item['shoe_variant_id']; ?>">
                                            <button type="submit" class="quantity-btn" aria-label="Increase quantity">+</button>
                                        </form>
                                    </div>

                                    <form action="customer/cart/update_cart.php" method="POST" class="cart-action-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cart_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="shoe_variant_id" value="<?php echo (int)$item['shoe_variant_id']; ?>">
                                        <button type="submit" class="remove-item-btn">Remove</button>
                                    </form>
                                </div>
                            </div>

                            <div class="item-subtotal">
                                <span>Subtotal</span>
                                <strong>RM<?php echo number_format($item['subtotal'], 2); ?></strong>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <section class="cart-summary" aria-label="Cart total">
                    <div class="cart-total">
                        <span>Total</span>
                        <strong>RM<?php echo number_format($total_price, 2); ?></strong>
                    </div>
                    <a class="checkout-btn" href="customer/checkout/checkout.php">Proceed to checkout</a>
                </section>
            <?php else: ?>
                <div class="empty-cart">
                    <h2>Your cart is empty</h2>
                    <p>Find a pair you love and it will appear here.</p>
                    <a class="checkout-btn" href="customer/browse.php">Browse shoes</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>