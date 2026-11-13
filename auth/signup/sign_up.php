<?php
require_once __DIR__ . '/../../config/db.php';

const ROLE_ADMIN    = 1;
const ROLE_STAFF    = 2;
const ROLE_CUSTOMER = 3;
const ALLOWED_SIGNUP_ROLES = [ROLE_CUSTOMER, ROLE_STAFF, ROLE_ADMIN];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $role_id = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT);
    if ($role_id === false || $role_id === null || !in_array($role_id, ALLOWED_SIGNUP_ROLES, true)) {
        $role_id = ROLE_CUSTOMER;
    }

    $check_user = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
                alert('Username already exists. Please choose a different username.');
                window.location.href = 'sign_up.html';
              </script>";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO user (username, password, role_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $password, $role_id);

    if ($stmt->execute()) {
        header("Location: ../../customer/browse.php");
        exit();
    } else {
        echo "<script>
                alert('Error occurred while registering.');
                window.location.href = 'sign_up.html';
              </script>";
        exit();
    }
}
?>