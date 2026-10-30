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

            $cart_data[] = [
                'name' => $row['name'],
                'price' => $row['price'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
    }
    $stmt->close();
?>  

