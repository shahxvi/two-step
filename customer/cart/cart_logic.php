<?php
require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$total_price = 0;
$cart_data = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT cart.quantity, shoe.shoe_id, shoe.shoe_name, shoe.shoe_price 
            FROM cart 
            JOIN shoe ON cart.shoe_id = shoe.shoe_id 
            WHERE cart.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['shoe_price'] * $row['quantity'];
        $total_price += $subtotal;

        $cart_data[] = [
            'shoe_id' => $row['shoe_id'],
            'name'    => $row['shoe_name'],
            'price'   => $row['shoe_price'],
            'quantity'=> $row['quantity'],
            'subtotal'=> $subtotal
        ];
    }
    $stmt->close();
}
?> 

