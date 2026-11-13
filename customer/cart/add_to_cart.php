<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shoe_id'])) {
    $shoe_id = $_POST['shoe_id'];
    $user_id = $_SESSION['user_id'];
    $quantity = 1; 

    $check = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND shoe_id = ?");
    $check->bind_param("ii", $user_id, $shoe_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $update = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND shoe_id = ?");
        $update->bind_param("ii", $user_id, $shoe_id);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO cart (user_id, shoe_id, quantity) VALUES (?, ?, ?)");
        $insert->bind_param("iii", $user_id, $shoe_id, $quantity);
        $insert->execute();
        $insert->close();
    }

    $check->close();

    header("Location: cart.php");
    exit();

} else {
    echo "Failed to receive data from POST!";
}
?>