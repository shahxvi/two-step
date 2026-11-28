<?php
session_start();
require_once __DIR__ . '/../config/authorization.php';
require_role(2);
require_once __DIR__ . '/../config/db.php';

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}
  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['order_id'];

    if ($_POST['action'] === 'remove_item') {
        $variantId = (int)$_POST['shoe_variant_id'];
        $stmt = $conn->prepare("DELETE FROM order_details WHERE customer_order_id = ? AND shoe_variant_id = ?");
        $stmt->bind_param("ii", $orderId, $variantId);
        $stmt->execute();

        header("Location: /two-step/staff/staff_orders.php?open=" . $orderId);
        exit;
    }

    if ($_POST['action'] === 'complete_order') {
        $stmt = $conn->prepare("UPDATE customer_order SET is_completed = 1 WHERE customer_order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        header("Location: /two-step/staff/staff_orders.php");
        exit;
    }
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
        WHERE co.is_completed = 0
        GROUP BY co.customer_order_id, co.order_date_time, u.fullname, u.username
        ORDER BY co.order_date_time DESC
        LIMIT 5";
$result = $conn->query($sql);

$orders = [];
while ($result && $row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>2 Step Sneakers: Staff Dashboard</title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="staff/staff_orders.css">
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>

        <nav>
            <a href="staff/staff_dashboard.php"><strong>Dashboard</strong></a> |
            <a href="staff/staff_orders.php"><strong>Orders</strong></a> |
            <a href="staff/staff_inventory.php"><strong>Inventory</strong></a> |
            <a href="auth/logout.php"><strong>Log Out</strong></a>
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
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $row): ?>
                            <tr>
                                <td>#ORD<?= str_pad($row['customer_order_id'], 3, "0", STR_PAD_LEFT) ?></td>
                                <td><?= e($row['customer_name']) ?></td>
                                <td><?= e($row['items_ordered']) ?></td>
                                <td>RM<?= number_format($row['total_price'], 0) ?></td>
                                <td><?= (int)$row['total_quantity'] ?></td>
                                <td><?= date("d M Y, h:i A", strtotime($row['order_date_time'])) ?></td>
                                <td><button class="manage-btn" onclick="openPopup(<?= (int)$row['customer_order_id'] ?>)">Manage</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="no-order">No orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php foreach ($orders as $row): ?>
            <?php
            $oid = (int)$row['customer_order_id'];

            $stmt = $conn->prepare("SELECT od.shoe_variant_id, s.shoe_name, s.shoe_price, od.quantity
                                    FROM order_details od
                                    JOIN shoe_variant sv ON od.shoe_variant_id = sv.shoe_variant_id
                                    JOIN shoe s ON sv.shoe_id = s.shoe_id
                                    WHERE od.customer_order_id = ?");
            $stmt->bind_param("i", $oid);
            $stmt->execute();
            $items = $stmt->get_result();
            ?>
            <div id="popup-<?= $oid ?>" class="popup-bg">
                <div class="popup-box">
                    <span class="close-btn" onclick="closePopup(<?= $oid ?>)">&times;</span>
                    <h2>Manage #ORD<?= str_pad($oid, 3, "0", STR_PAD_LEFT) ?></h2>

                    <table class="orders-table">
                        <thead><tr><th>Shoe</th><th>Price (RM)</th><th>Qty</th><th></th></tr></thead>
                        <tbody>
                        <?php if ($items->num_rows > 0): ?>
                            <?php while ($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($item['shoe_name']) ?></td>
                                <td>RM<?= number_format($item['shoe_price'], 2) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td>
                                    <form action="staff/staff_orders.php" method="POST">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="order_id" value="<?= $oid ?>">
                                        <input type="hidden" name="shoe_variant_id" value="<?= (int)$item['shoe_variant_id'] ?>">
                                        <button type="submit" class="remove-btn">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="no-order">No items in this order.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>

                    <form action="staff/staff_orders.php" method="POST"
                          onsubmit="return confirm('Mark this order as completed?');">
                        <input type="hidden" name="action" value="complete_order">
                        <input type="hidden" name="order_id" value="<?= $oid ?>">
                        <button type="submit" class="complete-btn">Mark as completed</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </main>

    <script>
        function openPopup(id)  { document.getElementById("popup-" + id).style.display = "flex"; }
        function closePopup(id) { document.getElementById("popup-" + id).style.display = "none"; }

        const open = new URLSearchParams(window.location.search).get("open");
        if (open && document.getElementById("popup-" + open)) openPopup(open);
    </script>
</body>
</html>