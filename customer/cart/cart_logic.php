<?php
require_once __DIR__ . '/../../config/authorization.php';
require_role(3);
require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$total_price = 0;
$cart_data = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT cart.cart_id, cart.shoe_variant_id, cart.quantity, shoe.shoe_id, shoe.shoe_name, shoe.shoe_price, size.size_uk, color.color_name
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
        $subtotal = $row['shoe_price'] * $row['quantity'];
        $total_price += $subtotal;

        $cart_data[] = [
            'cart_id'  => $row['cart_id'],
            'shoe_variant_id'=> $row['shoe_variant_id'],
            'name'     => $row['shoe_name'],
            'price'    => $row['shoe_price'],
            'quantity' => $row['quantity'],
            'size'     => $row['size_uk'],  
            'color'    => $row['color_name'], 
            'subtotal' => $subtotal
        ];
    }
    $stmt->close();
}
?>

