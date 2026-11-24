<?php
require_once __DIR__ . '/../../config/db.php';

const ROLE_ADMIN    = 1;
const ROLE_STAFF    = 2;
const ROLE_CUSTOMER = 3;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || strlen($password) < 6) {
        echo "<script>
                alert('Enter a username and a password of at least 6 characters.');
                window.location.href = 'sign_up.html';
              </script>";
        exit();
    }

    $role_id = ROLE_CUSTOMER;

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

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO user (username, password, role_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $password_hash, $role_id);

    if ($stmt->execute()) {
        session_start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['role_id'] = $role_id;

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