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
            $_SESSION['username'] = $username;
            header("Location: browse.html");
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
        $stmt->close();
    }
?>