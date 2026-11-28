<?php
require_once __DIR__ . '/../../config/authorization.php';
require_role(3);
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['cart_csrf_token'])) {
    $_SESSION['cart_csrf_token'] = bin2hex(random_bytes(32));
}
$cart_csrf_token = $_SESSION['cart_csrf_token'];

$total_price = 0;
$cart_data = [];

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT cart.cart_id, cart.shoe_variant_id, cart.quantity, shoe.shoe_name,
               shoe_variant.shoe_price, size.size_uk, color.color_name
        FROM cart
        JOIN shoe_variant ON cart.shoe_variant_id = shoe_variant.shoe_variant_id
        JOIN shoe ON shoe_variant.shoe_id = shoe.shoe_id
        JOIN size ON shoe_variant.size_id = size.size_id
        JOIN color ON shoe_variant.color_id = color.color_id
        WHERE cart.user_id = ?
        ORDER BY cart.cart_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $subtotal = (float)$row['shoe_price'] * (int)$row['quantity'];
    $total_price += $subtotal;

    $cart_data[] = [
        'cart_id' => (int)$row['cart_id'],
        'shoe_variant_id' => (int)$row['shoe_variant_id'],
        'name' => $row['shoe_name'],
        'price' => (float)$row['shoe_price'],
        'quantity' => (int)$row['quantity'],
        'size' => $row['size_uk'],
        'color' => $row['color_name'],
        'subtotal' => $subtotal,
    ];
}
$stmt->close();

