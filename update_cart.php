<?php
session_start();
include 'db.php';

if (isset($_GET['action']) && isset($_GET['shoe_id'])) {
    $user_id = $_SESSION['user_id'];
    $shoe_id = $_GET['shoe_id'];
    $action  = $_GET['action'];

    if ($action === 'add') {
        $stmt = $conn->prepare("UPDATE CART SET quantity = quantity + 1 WHERE user_id = ? AND shoe_id = ?");
        $stmt->bind_param("ii", $user_id, $shoe_id);
        $stmt->execute();
        $stmt->close();

    } elseif ($action === 'minus') {
        $check = $conn->prepare("SELECT quantity FROM CART WHERE user_id = ? AND shoe_id = ?");
        $check->bind_param("ii", $user_id, $shoe_id);
        $check->execute();
        $res = $check->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if ($row['quantity'] > 1) {
                $update = $conn->prepare("UPDATE CART SET quantity = quantity - 1 WHERE user_id = ? AND shoe_id = ?");
                $update->bind_param("ii", $user_id, $shoe_id);
                $update->execute();
                $update->close();
            } else {
                $delete = $conn->prepare("DELETE FROM CART WHERE user_id = ? AND shoe_id = ?");
                $delete->bind_param("ii", $user_id, $shoe_id);
                $delete->execute();
                $delete->close();
            }
        }
        $check->close();

    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM CART WHERE user_id = ? AND shoe_id = ?");
        $stmt->bind_param("ii", $user_id, $shoe_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: cart.php");
exit();
?>