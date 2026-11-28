<?php
session_start();
require_once __DIR__ . '/../../config/authorization.php';
require_role(3);
require_once __DIR__ . '/../../config/db.php';

if (isset($_GET['action']) && isset($_GET['shoe_variant_id'])) {
    $user_id = $_SESSION['user_id'];
    $shoe_variant_id = $_GET['shoe_variant_id'];
    $action  = $_GET['action'];

    if ($action === 'add') {
        $check = $conn->prepare(
            "SELECT c.cart_id, c.quantity, sv.quantity AS stock_quantity, sv.status_id
             FROM cart AS c
             JOIN shoe_variant AS sv ON sv.shoe_variant_id = c.shoe_variant_id
             WHERE c.user_id = ? AND c.shoe_variant_id = ?"
        );
        $check->bind_param("ii", $user_id, $shoe_variant_id);
        $check->execute();
        $item = $check->get_result()->fetch_assoc();
        $check->close();

        if (
            !$item ||
            (int)$item['status_id'] !== 1 ||
            (int)$item['quantity'] >= (int)$item['stock_quantity']
        ) {
            header("Location: /two-step/customer/cart/cart.php?error=stock_limit");
            exit;
        }

        $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE cart_id = ?");
        $stmt->bind_param("i", $item['cart_id']);
        $stmt->execute();
        $stmt->close();

    } elseif ($action === 'minus') {
        $check = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND shoe_variant_id = ?");
        $check->bind_param("ii", $user_id, $shoe_variant_id);
        $check->execute();
        $res = $check->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if ($row['quantity'] > 1) {
                $update = $conn->prepare("UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND shoe_variant_id = ?");
                $update->bind_param("ii", $user_id, $shoe_variant_id);
                $update->execute();
                $update->close();
            } else {
                $delete = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND shoe_variant_id = ?");
                $delete->bind_param("ii", $user_id, $shoe_variant_id);
                $delete->execute();
                $delete->close();
            }
        }
        $check->close();

    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND shoe_variant_id = ?");
        $stmt->bind_param("ii", $user_id, $shoe_variant_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: cart.php");
exit();
?>