<?php
session_start();
require_once __DIR__ . '/../../config/authorization.php';
require_role(3);
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$userId = (int)$_SESSION['user_id'];
$shoeVariantId = filter_var($_POST['shoe_variant_id'] ?? null, FILTER_VALIDATE_INT);

if ($shoeVariantId === false || $shoeVariantId === null || $shoeVariantId < 1) {
    http_response_code(400);
    exit('A valid shoe variant is required.');
}

$variant = $conn->prepare(
    "SELECT s.shoe_id, sv.quantity, sv.status_id
     FROM shoe_variant AS sv
     JOIN shoe AS s ON s.shoe_id = sv.shoe_id
     WHERE sv.shoe_variant_id = ?"
);
$variant->bind_param("i", $shoeVariantId);
$variant->execute();
$variantData = $variant->get_result()->fetch_assoc();
$variant->close();

if (!$variantData) {
    http_response_code(404);
    exit('Shoe variant not found.');
}

if ((int)$variantData['quantity'] < 1 || (int)$variantData['status_id'] !== 1) {
    header("Location: /two-step/customer/shoe_detail.php?id=" . (int)$variantData['shoe_id'] . "&error=out_of_stock");
    exit;
}

$check = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND shoe_variant_id = ?");
$check->bind_param("ii", $userId, $shoeVariantId);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ((int)$row['quantity'] >= (int)$variantData['quantity']) {
        header("Location: /two-step/customer/cart/cart.php?error=stock_limit");
        exit;
    }
    $cartId = (int)$row['cart_id'];

    $update = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE cart_id = ?");
    $update->bind_param("i", $cartId);
    $update->execute();
    $update->close();
} else {
    $quantity = 1;
    $insert = $conn->prepare("INSERT INTO cart (user_id, shoe_variant_id, quantity) VALUES (?, ?, ?)");
    $insert->bind_param("iii", $userId, $shoeVariantId, $quantity);
    $insert->execute();
    $insert->close();
}

$check->close();
header("Location: /two-step/customer/cart/cart.php");
exit();
?>