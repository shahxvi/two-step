<?php
echo "<h1>Welcome to My Local Project!</h1>";

// Test MySQL connection
$conn = new mysqli("localhost", "root", "482377");

if ($conn->connect_error) {
    echo "<p style='color:red;'>Database Connection Failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green;'>Successfully connected to MySQL database!</p>";
}
?>
