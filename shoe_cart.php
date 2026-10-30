<?php
include ("db.php");

if (isset($_SESSION["cart"]) && !empty($_SESSION["cart"])) {
    echo "<p style= 'text-align: center; padding: 20px;'>Your cart is empty <a href='browse.html'>Browse Shoes</a></p>";
} else {
    $total_price = 0;
    $cart_items = array_count_values($_SESSION['cart']);
    }

    $stmt = $conn->prepare("SELECT name, price, image FROM products WHERE shoe_id = ?");
    $stmt->bind_param("i", $shoe_id);
    $stmt->execute();
    $result = $stmt->get_result();  

    if ($row = $result->fetch_assoc()) {
            $subtotal = $row['price'] * $quantity;
            $total_price += $subtotal;

    echo '<div class="cart-item" style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">';
    echo '<div>';
    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
    echo '<p>Price: RM ' . number_format($row['price'], 2) . '</p>';
    echo '<p>Quantity: ' . $quantity . '</p>';
    echo '</div>';
    echo '<div>';
    echo '<p>Subtotal: RM ' . number_format($subtotal, 2) . '</p>';
    echo '</div>';
    echo '</div>';
    }
    $stmt->close();
?>  

