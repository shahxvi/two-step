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

            $sizeId   = (int)$_POST['size_id'];
            $colorId  = (int)$_POST['color_id'];
            $statusId = (int)$_POST['status_id'];

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

    <style>
        .manage-btn { background: #7d7266; color: white; border: none; padding: 6px 14px;
                      border-radius: 5px; cursor: pointer; }
        .open-add-btn { background: #2c3e50; color: white; border: none; padding: 10px 18px;
                        border-radius: 5px; cursor: pointer; margin-top: 15px; }
        .popup-bg { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000; }
        .popup-box { background: #e8e6e1; width: 90%; max-width: 450px; max-height: 85vh;
                     overflow-y: auto; padding: 20px; border-radius: 10px; position: relative; }
        .close-btn { position: absolute; top: 10px; right: 15px; font-size: 28px; cursor: pointer; }
        .popup-box input[type=text], .popup-box input[type=number] {
                     padding: 8px; margin: 6px 0; width: 100%; box-sizing: border-box; }
        .add-btn { background: #2c3e50; color: white; border: none; padding: 10px 18px;
                   border-radius: 5px; cursor: pointer; margin-top: 6px; }
        .remove-btn { background: #c0392b; color: white; border: none; padding: 10px 18px;
                      border-radius: 5px; cursor: pointer; margin-top: 10px; }
        .notice { margin-bottom: 15px; padding: 10px 15px; background: #fff; border-radius: 5px; }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>

        <nav>
            <a><strong>Dashboard</strong></a> |
            <a href="staff/staff_dashboard.php"><strong>Orders</strong></a> |
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
                        <th>Product ID</th>
                        <th>Shoe Name</th>
                        <th>Price (RM)</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $row): ?>
                            <tr>
                                <td>#SHOE<?= str_pad($row['shoe_id'], 3, "0", STR_PAD_LEFT) ?></td>
                                <td><?= e($row['shoe_name']) ?></td>
                                <td>RM<?= number_format($row['shoe_price'], 0) ?></td>
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

        <!-- MANAGE POPUP FOR EACH PRODUCT -->
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

        <div id="popup-add" class="popup-bg">
            <div class="popup-box">
                <span class="close-btn" onclick="closePopup('add')">&times;</span>
                <h2>Add Product</h2>

                <form action="staff/staff_inventory.php" method="POST">
                    <input type="hidden" name="action" value="add_product">
                    <input type="text" name="shoe_name" placeholder="Shoe name" required>
                    <input type="number" name="shoe_price" step="0.01" min="0" placeholder="Price (RM)" required>
                    <input type="number" name="size_id" min="1" placeholder="Size ID" required>
                    <input type="number" name="color_id" min="1" placeholder="Color ID" required>
                    <input type="number" name="status_id" min="1" placeholder="Status ID" required>
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