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

$sql = "SELECT
            co.customer_order_id,
            COALESCE(u.fullname, u.username) AS customer_name,
            COALESCE(GROUP_CONCAT(DISTINCT s.shoe_name SEPARATOR ', '), '-') AS items_ordered,
            COALESCE(SUM(s.shoe_price * od.quantity), 0) AS total_price,
            COALESCE(SUM(od.quantity), 0) AS total_quantity,
            co.order_date_time
        FROM customer_order co
        JOIN user u ON co.user_id = u.user_id
        LEFT JOIN order_details od ON co.customer_order_id = od.customer_order_id
        LEFT JOIN shoe_variant sv ON od.shoe_variant_id = sv.shoe_variant_id
        LEFT JOIN shoe s ON sv.shoe_id = s.shoe_id
        GROUP BY co.customer_order_id, co.order_date_time, u.fullname, u.username
        ORDER BY co.order_date_time DESC
        LIMIT 5";

$result = $conn->query($sql);
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

        <nav>
            <a><strong>Dashboard</strong></a> |
            <a><strong>Orders</strong></a> |
            <a><strong>Inventory</strong></a> |
            <a href="auth/login/login.html"><strong>Log Out</strong></a>
        </nav>
    </header>

    <main>
        <h1>Hello <?= e($staffName) ?>!</h1>

        <div class="panel orders-panel">
            <h2><strong>Orders:</strong></h2>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Items Ordered</th>
                        <th>Price (RM)</th>
                        <th>Quantity</th>
                        <th>Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#ORD<?php echo str_pad($row['customer_order_id'], 3, "0", STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['items_ordered']); ?></td>
                                <td>RM<?php echo number_format($row['total_price'], 0); ?></td>
                                <td><?php echo (int)$row['total_quantity']; ?></td>
                                <td><?php echo date("d M Y, h:i A", strtotime($row['order_date_time'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-order">No orders yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
    </main>
</body>
</html>