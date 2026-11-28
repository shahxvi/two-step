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
        <div class="cart-container">
            <h1 class="cart-title"><strong>Shopping Cart</strong></h1>
            <div class="cart-items">
                <?php if (!empty($cart_data)): ?>
                    <?php foreach ($cart_data as $item): ?>
                        <div class="cart-item-row">
                            <div class="item-details">
                                <b>Name: <?php echo htmlspecialchars($item['name']); ?></b> |
                                <b>Price: RM<?php echo number_format($item['price'], 2); ?></b> |
                                <b>Size: <?php echo htmlspecialchars($item['size']); ?></b> |
                                <b>Color: <?php echo htmlspecialchars($item['color']); ?></b> |

                                <span style="margin: 0 10px;">
                                    <a href="customer/cart/update_cart.php?action=minus&shoe_variant_id=<?php echo $item['shoe_variant_id']; ?>" class="add-cart-btn">-</a>
                                    <b>Quantity: <?php echo $item['quantity']; ?></b>
                                    <a href="customer/cart/update_cart.php?action=add&shoe_variant_id=<?php echo $item['shoe_variant_id']; ?>" class="add-cart-btn">+</a>
                                </span> |

                                <b>Subtotal: RM<?php echo number_format($item['subtotal'], 2); ?></b>

                                <a href="customer/cart/update_cart.php?action=remove&shoe_variant_id=<?php echo $item['shoe_variant_id']; ?>" class="remove-item-btn">Remove</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 20px;">Your cart is empty.</p>
                <?php endif; ?>
            </div>
            
            <div class="cart-summary">
                <p>Total: RM<?php echo number_format($total_price, 2); ?></p>
                <button type="button" class="continue-shopping-btn" onclick="window.location.href='customer/browse.php'">Continue Shopping</button>
                <button type="button" class="checkout-btn" onclick="window.location.href='customer/checkout/checkout.php'">Checkout</button>
            </div>
        </div>
    </main>
</body>
</html>