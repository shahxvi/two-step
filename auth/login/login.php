<?php
require_once __DIR__ . '/../../config/db.php';

const ROLE_ADMIN    = 1;
const ROLE_STAFF    = 2;
const ROLE_CUSTOMER = 3;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT user_id, password, role_id FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $password_valid = password_verify($password, $user['password']);

        // Upgrade existing plaintext passwords after a successful login.
        if (!$password_valid && $user['password'] === $password) {
            $password_valid = true;
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $upgrade = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
            $upgrade->bind_param("si", $password_hash, $user['user_id']);
            $upgrade->execute();
        }

        // Rehash when PHP's default algorithm or cost has changed.
        if ($password_valid && password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $upgrade = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
            $upgrade->bind_param("si", $password_hash, $user['user_id']);
            $upgrade->execute();
        }

        if ($password_valid) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['role_id'] = (int) $user['role_id'];

            if ((int) $user['role_id'] === ROLE_ADMIN) {
                header("Location:../../admin/admin_dashboard.php");
                exit();
            } else if ((int) $user['role_id'] === ROLE_STAFF) {
                header("Location:../../staff/staff_dashboard.php");
                exit();
            } else if ((int) $user['role_id'] === ROLE_CUSTOMER) {
                header("Location:../../customer/browse.php");
                exit();
            }
        }
    }

    echo "<script>
            alert('Invalid username or password.');
            window.location.href = 'login.html';
          </script>";
    exit();
}
?>