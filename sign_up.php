<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); //Hash the password for security

    $check_user = $conn->prepare("SELECT user_id FROM USER WHERE username = ? ");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows > 0) {
        $error = "Username already exists. Please choose a different username.";
    } else {
        $stmt = $conn->prepare("INSERT INTO USER(username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            header("Location: login.php?success=registered");
            exit();
        } else {
            $error = "Error occurred while registering.";
        }
    }
}
?>