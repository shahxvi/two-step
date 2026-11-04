<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

$total_price = 0;
$cart_data = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT cart.quantity, SHOE.shoe_id, SHOE.shoe_name, SHOE.shoe_price 
            FROM CART 
            JOIN SHOE ON CART.shoe_id = SHOE.shoe_id 
            WHERE CART.user_id = ?";

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

