<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $check_user = $conn->prepare("SELECT user_id FROM USER WHERE username = ? ");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
                alert('Username already exists. Please choose a different username.');
                window.location.href = 'sign_up.html';
                </script>";
        exit();
    } else {
        $stmt = $conn->prepare("INSERT INTO USER(username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            header("Location: browse.html");
            exit();
        } else {
            $error = "Error occurred while registering.";
        }
    }
}
?>