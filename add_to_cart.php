<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shoe_id'])) {
    $shoe_id = $_POST['shoe_id'];
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $_SESSION['cart'][] = $shoe_id;
    header("Location: cart.php");
    exit();
} else {
    echo "Gagal terima data POST!";
}

