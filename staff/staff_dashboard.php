<?php
require_once __DIR__ . '/../config/db.php';

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

$sql = "SELECT customer_order_id, user_id, status_id,
        FROM customer_order
        LIMIT 5";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>2 Step Sneakers: Sign Up</title>
    <link rel="icon" type="image/jpg" href="images/logo.png">
    <link rel="stylesheet" href="staff_dashboard.css">
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
            <div class="black-line"></div>
            <h1>Hello Staff!</h1>
            <div class="panel orders-panel">
        <h2>Current order status</h2>
    
        <?php if (empty($orders)): ?>
            <div class="row empty">No active orders right now.</div>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <a class="row" href="order_details.php?id=<?= (int)$o['order_id'] ?>">
                    <span class="order-id">#<?= (int)$o['order_id'] ?></span>
                    <span class="cust"><?= e($o['full_name']) ?></span>
                    <span class="date"><?= date('d M Y, h:i A', strtotime($o['order_date'])) ?></span>
                    <span class="total">RM <?= number_format($o['total'], 2) ?></span>
                    <span class="badge <?= strtolower(e($o['status'])) ?>"><?= e($o['status']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
            </div>
        </div>
    </header>
</body>
</html>