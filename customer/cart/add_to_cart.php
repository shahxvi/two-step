<?php
session_start();
require_once __DIR__ . '/../../config/authorization.php';
require_role(3);
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$submittedToken = $_POST['csrf_token'] ?? '';
$expectedToken = $_SESSION['cart_csrf_token'] ?? '';
if (
    !is_string($submittedToken) ||
    !is_string($expectedToken) ||
    $expectedToken === '' ||
    !hash_equals($expectedToken, $submittedToken)
) {
    http_response_code(403);
    exit('Invalid form token.');
}

$userId = (int)$_SESSION['user_id'];
$shoeVariantId = filter_var($_POST['shoe_variant_id'] ?? null, FILTER_VALIDATE_INT);

if ($shoeVariantId === false || $shoeVariantId === null || $shoeVariantId < 1) {
    http_response_code(400);
    exit('A valid shoe variant is required.');
}

$conn->begin_transaction();
try {
    $variant = $conn->prepare(
        'SELECT shoe_id, quantity, status_id
         FROM shoe_variant
         WHERE shoe_variant_id = ?
         FOR UPDATE'
    );
    $variant->bind_param('i', $shoeVariantId);
    $variant->execute();
    $variantData = $variant->get_result()->fetch_assoc();
    $variant->close();

    if (!$variantData) {
        $conn->rollback();
        http_response_code(404);
        exit('Shoe variant not found.');
    }

    if ((int)$variantData['quantity'] < 1 || (int)$variantData['status_id'] !== 1) {
        $conn->rollback();
        header('Location: /two-step/customer/shoe_detail.php?id=' . (int)$variantData['shoe_id'] . '&error=out_of_stock');
        exit;
    }

    $check = $conn->prepare(
        'SELECT cart_id, quantity FROM cart
         WHERE user_id = ? AND shoe_variant_id = ?
         ORDER BY cart_id
         FOR UPDATE'
    );
    $check->bind_param('ii', $userId, $shoeVariantId);
    $check->execute();
    $cartRows = $check->get_result()->fetch_all(MYSQLI_ASSOC);
    $check->close();

    $cartQuantity = array_sum(array_map(
        static fn(array $row): int => (int)$row['quantity'],
        $cartRows
    ));
    if ($cartQuantity >= (int)$variantData['quantity']) {
        $conn->rollback();
        header('Location: /two-step/customer/cart/cart.php?error=stock_limit');
        exit;
    }

    if ($cartRows) {
        $primaryCartId = (int)$cartRows[0]['cart_id'];
        $newQuantity = $cartQuantity + 1;
        $update = $conn->prepare('UPDATE cart SET shoe_id = ?, quantity = ? WHERE cart_id = ? AND user_id = ?');
        $update->bind_param('iiii', $variantData['shoe_id'], $newQuantity, $primaryCartId, $userId);
        $update->execute();
        $update->close();

        $removeDuplicates = $conn->prepare(
            'DELETE FROM cart WHERE user_id = ? AND shoe_variant_id = ? AND cart_id <> ?'
        );
        $removeDuplicates->bind_param('iii', $userId, $shoeVariantId, $primaryCartId);
        $removeDuplicates->execute();
        $removeDuplicates->close();
    } else {
        $quantity = 1;
        $insert = $conn->prepare(
            'INSERT INTO cart (shoe_id, shoe_variant_id, user_id, quantity) VALUES (?, ?, ?, ?)'
        );
        $insert->bind_param('iiii', $variantData['shoe_id'], $shoeVariantId, $userId, $quantity);
        $insert->execute();
        $insert->close();
    }

    $conn->commit();
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    throw $exception;
}

header("Location: /two-step/customer/cart/cart.php");
exit();
?>