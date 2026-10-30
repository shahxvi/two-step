<?php
include ("db.php");

if (isset($_SESSION["cart"]) && !empty($_SESSION["cart"])) {
    $_SESSION['cart'] = [];
} else {
    $total_price = 0;
    $cart_items = array_count_values($_SESSION['cart']);
    }

    $stmt = $conn->prepare("SELECT shoe_name, shoe_price, image FROM shoe WHERE shoe_id = ?");
    $stmt->bind_param("i", $shoe_id);
    $stmt->execute();
    $result = $stmt->get_result();  

    if ($row = $result->fetch_assoc()) {
            $subtotal = $row['shoe_price'] * $quantity;
            $total_price += $subtotal;

            $cart_data[] = [
                'name' => $row['shoe_name'],
                'price' => $row['shoe_price'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
    }
    $stmt->close();
?>  

