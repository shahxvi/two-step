<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shoe_id'])) {
    $shoe_id = $_POST['shoe_id'];

    echo "Product ID yang berjaya dihantar ialah: " . $shoe_id;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $_SESSION['cart'][] = $shoe_id;

    echo "<br><a href='cart.php'>Pergi ke Cart sekarang</a>";
    exit();
} else {
    echo "Gagal terima data POST!";
}

