<?php
include 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password FROM USER WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            session_start();         
            $_SESSION['user_id'] = $user['user_id'];
            header("Location: browse.html");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please sign up before logging in.";
        }
        $stmt->close();
    }
?>