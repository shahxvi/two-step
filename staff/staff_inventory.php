<?php
session_start();
require_once __DIR__ . '/../config/authorization.php';
require_role(2);
require_once __DIR__ . '/../config/db.php';

$shoe_sql = "SELECT
                sv.shoe_variant_id,
                b.brand_name,
                s.shoe_name,
                sv.shoe_price,
                sz.size_uk,
                c.color_name,
                sv.quantity
             FROM shoe_variant AS sv
             JOIN shoe AS s ON sv.shoe_id = s.shoe_id
             JOIN brand AS b ON s.brand_id = b.brand_id
             JOIN size AS sz ON sv.size_id = sz.size_id
             JOIN color AS c ON sv.color_id = c.color_id
             ORDER BY b.brand_name ASC, s.shoe_name ASC, sz.size_uk ASC";
$shoe_result = $conn->query($shoe_sql);
if (!$shoe_result) {
    die("Could not load inventory: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>2 Step Sneakers: Staff Inventory</title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="staff/staff_inventory.css">
    <link rel="stylesheet" href="site-nav.css">
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
        <div class="panel inventory-panel">
            <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
                <div class="notice">Shoe details updated successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['added']) && $_GET['added'] === '1'): ?>
                <div class="notice">Shoe added successfully.</div>
            <?php endif; ?>
            <div class="inventory-heading">
                <h2>Inventory</h2>
                <a class="add-btn" href="staff/staff_inventory_add.php" aria-label="Add a new shoe">Add</a>
            </div>
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Shoe Brand</th>
                        <th>Shoe Name</th>
                        <th>Price (RM)</th>
                        <th>Size (UK)</th>
                        <th>Color</th>
                        <th>Quantity</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($shoe_result->num_rows > 0): ?>
                        <?php while ($row = $shoe_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['brand_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['shoe_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float)$row['shoe_price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['size_uk'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['color_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$row['quantity'] ?></td>
                                <td>
                                    <a class="manage-btn" href="staff/staff_inventory_manage.php?shoe_variant_id=<?= (int)$row['shoe_variant_id'] ?>">Manage</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-inventory">No inventory records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>