<?php
// PHP MySQLi Object-Oriented Connection
$servername = "localhost";
$username = "root";
$password = "482377";
$dbname = "two_step";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}
// echo "Connected successfully";
?>