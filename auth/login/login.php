<?php
require_once __DIR__ . '/../../config/db.php';

const ROLE_ADMIN    = 1;
const ROLE_STAFF    = 2;
const ROLE_CUSTOMER = 3;
const ALLOWED_SIGNUP_ROLES = [ROLE_CUSTOMER, ROLE_STAFF, ROLE_ADMIN];

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role_id = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT);
    if ($role_id === false || $role_id === null || !in_array($role_id, ALLOWED_SIGNUP_ROLES, true)) {
        $role_id = ROLE_CUSTOMER;
    }

    $stmt = $conn->prepare("SELECT user_id, password, role_id FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($password === $user['password']) {
            session_start();         
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['role_id'] = $user['role_id'];

            if ($user['role_id'] === 1) {
                header("Location:../../admin/admin_dashboard.php");
                exit();
            } else if ($user['role_id'] === 2) {
                header("Location:../../staff/staff_dashboard.php");
                exit();
            }  else if ($user['role_id'] === 3) {
                header("Location:../../customer/browse.php");
                exit();
            } else {
            echo "<script>
                    alert('Invalid username or password.');
                    window.location.href = 'login.html';
                  </script>";
            exit();
        }
    } else {
        echo "<script>
                    alert('Please sign up before logging in.');
                    window.location.href = 'login.html';
                  </script>";
        exit();
        }
    }
}
?>