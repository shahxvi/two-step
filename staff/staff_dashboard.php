<?php
session_start();
require_once __DIR__ . '/../config/db.php';

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

$stmt = $conn->prepare("SELECT username FROM user WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$staffName = $staff ? $staff['username'] : "Staff";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>2 Step Sneakers: Staff Dashboard</title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="staff/staff_dashboard.css">
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>
    </header>

    <main>
        <h1>Hello <?= e($staffName) ?>!</h1>

        <div class="panel orders-panel">
            <h2>Current order status</h2>
            <div class="row empty">No active orders right now.</div>
        </div>
    </main>
</body>
</html>