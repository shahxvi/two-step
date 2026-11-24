<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /two-step/auth/login/login.html");
    exit;
}

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = '';

    try {
        if ($_POST['action'] === 'add_product') {
            $conn->begin_transaction();

            $stmt = $conn->prepare("INSERT INTO shoe (shoe_name, shoe_price) VALUES (?, ?)");
            $stmt->bind_param("sd", $_POST['shoe_name'], $_POST['shoe_price']);
            $stmt->execute();
            $shoeId = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO shoe_variant (shoe_id) VALUES (?)");
            $stmt->bind_param("i", $shoeId);
            $stmt->execute();

            $conn->commit();
            $msg = "Product added.";
        }

        if ($_POST['action'] === 'remove_product') {
            $shoeId = (int)$_POST['shoe_id'];
            $conn->begin_transaction();

            $stmt = $conn->prepare("DELETE FROM shoe_variant WHERE shoe_id = ?");
            $stmt->bind_param("i", $shoeId);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM shoe WHERE shoe_id = ?");
            $stmt->bind_param("i", $shoeId);
            $stmt->execute();

            $conn->commit();
            $msg = "Product removed.";
        }
    } catch (mysqli_sql_exception $ex) {
        $conn->rollback();
        $msg = "Could not complete that. This product may already be in a customer order.";
    }

    header("Location: /two-step/staff/staff_inventory.php?msg=" . urlencode($msg));
    exit;
}

$result = $conn->query("SELECT shoe_id, shoe_name, shoe_price FROM shoe ORDER BY shoe_id DESC");

$products = [];
while ($result && $row = $result->fetch_assoc()) {
    $products[] = $row;
}

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>2 Step Sneakers: Staff Inventory</title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="staff/staff_dashboard.css">
    <link rel="stylesheet" href="staff/staff_inventory.css">
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>

        <nav>
            <a href="staff/staff_dashboard.php"><strong>Dashboard</strong></a> |
            <a><strong>Orders</strong></a> |
            <a><strong>Inventory</strong></a> |
            <a href="auth/login/login.html"><strong>Log Out</strong></a>
        </nav>
    </header>

    <main>
        <div class="panel orders-panel">
            <h2><strong>Products:</strong></h2>

            <?php if ($msg): ?>
                <div class="notice"><?= e($msg) ?></div>
            <?php endif; ?>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Shoe ID</th>
                        <th>Shoe Name</th>
                        <th>Price (RM)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $row): ?>
                            <tr>
                                <td>#SHOE<?= str_pad($row['shoe_id'], 3, "0", STR_PAD_LEFT) ?></td>
                                <td><?= e($row['shoe_name']) ?></td>
                                <td><?= number_format($row['shoe_price'], 0) ?></td>
                                <td><button class="manage-btn" onclick="openPopup('manage-<?= (int)$row['shoe_id'] ?>')">Manage</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="no-order">No products yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <button class="open-add-btn" onclick="openPopup('add')">Add Product</button>
        </div>

        <?php foreach ($products as $row): ?>
            <div id="popup-manage-<?= (int)$row['shoe_id'] ?>" class="popup-bg">
                <div class="popup-box">
                    <span class="close-btn" onclick="closePopup('manage-<?= (int)$row['shoe_id'] ?>')">&times;</span>
                    <h2>Manage Product</h2>
                    <p><strong><?= e($row['shoe_name']) ?></strong></p>
                    <p>Price: RM<?= number_format($row['shoe_price'], 2) ?></p>

                    <form action="staff/staff_inventory.php" method="POST"
                          onsubmit="return confirm('Remove this product?');">
                        <input type="hidden" name="action" value="remove_product">
                        <input type="hidden" name="shoe_id" value="<?= (int)$row['shoe_id'] ?>">
                        <button type="submit" class="remove-btn">Remove product</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- add product -->
        <div id="popup-add" class="popup-bg">
            <div class="popup-box">
                <span class="close-btn" onclick="closePopup('add')">&times;</span>
                <h2>Add Product</h2>

                <form action="staff/staff_inventory.php" method="POST">
                    <input type="hidden" name="action" value="add_product">
                    <input type="text" name="shoe_name" placeholder="Shoe name" required>
                    <input type="number" name="shoe_price" step="0.01" min="0" placeholder="Price (RM)" required>
                    <button type="submit" class="add-btn">Add</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function openPopup(id)  { document.getElementById("popup-" + id).style.display = "flex"; }
        function closePopup(id) { document.getElementById("popup-" + id).style.display = "none"; }
    </script>
</body>
</html>