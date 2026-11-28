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
$action = $_POST['action'] ?? '';

if ($shoeVariantId === false || $shoeVariantId === null || $shoeVariantId < 1) {
    http_response_code(400);
    exit('A valid shoe variant is required.');
}
if (!is_string($action) || !in_array($action, ['add', 'minus', 'remove'], true)) {
    http_response_code(400);
    exit('Invalid cart action.');
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

    if ($action === 'remove') {
        $delete = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND shoe_variant_id = ?');
        $delete->bind_param('ii', $userId, $shoeVariantId);
        $delete->execute();
        $delete->close();
    } elseif (!$variantData || !$cartRows) {
        $conn->rollback();
        header('Location: /two-step/customer/cart/cart.php?error=stock_limit');
        exit;
    } else {
        $cartQuantity = array_sum(array_map(
            static fn(array $row): int => (int)$row['quantity'],
            $cartRows
        ));

        if (
            $action === 'add' &&
            ((int)$variantData['status_id'] !== 1 ||
                $cartQuantity >= (int)$variantData['quantity'])
        ) {
            $conn->rollback();
            header('Location: /two-step/customer/cart/cart.php?error=stock_limit');
            exit;
        }

        $primaryCartId = (int)$cartRows[0]['cart_id'];
        $newQuantity = $action === 'add' ? $cartQuantity + 1 : $cartQuantity - 1;
        if ($newQuantity < 1) {
            $delete = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND shoe_variant_id = ?');
            $delete->bind_param('ii', $userId, $shoeVariantId);
            $delete->execute();
            $delete->close();
        } else {
            $update = $conn->prepare(
                'UPDATE cart SET shoe_id = ?, quantity = ? WHERE cart_id = ? AND user_id = ?'
            );
            $update->bind_param('iiii', $variantData['shoe_id'], $newQuantity, $primaryCartId, $userId);
            $update->execute();
            $update->close();

            $removeDuplicates = $conn->prepare(
                'DELETE FROM cart WHERE user_id = ? AND shoe_variant_id = ? AND cart_id <> ?'
            );
            $removeDuplicates->bind_param('iii', $userId, $shoeVariantId, $primaryCartId);
            $removeDuplicates->execute();
            $removeDuplicates->close();
        }
    }

    $conn->commit();
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    throw $exception;
}

header('Location: /two-step/customer/cart/cart.php');
exit();
?>