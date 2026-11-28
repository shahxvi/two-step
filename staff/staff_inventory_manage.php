<?php
session_start();
require_once __DIR__ . '/../config/authorization.php';
require_role(2);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/shoe_image_upload.php';

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

$shoeVariantId = (int)($_POST['shoe_variant_id'] ?? $_GET['shoe_variant_id'] ?? 0);
$message = '';
 $savedImagePath = null;

if ($shoeVariantId < 1) {
    header("Location: /two-step/staff/staff_inventory.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionStarted = false;

    try {
        $conn->begin_transaction();
        $transactionStarted = true;

        $imagePath = null;
        $oldImagePath = null;

        $oldStmt = $conn->prepare("SELECT image_path FROM shoe WHERE shoe_id = ?");
        $oldStmt->bind_param("i", $_POST['shoe_id']);
        $oldStmt->execute();
        $oldResult = $oldStmt->get_result()->fetch_assoc();
        if ($oldResult) {
            $oldImagePath = $oldResult['image_path'];
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagePath = upload_shoe_image($_FILES['image']);
            $savedImagePath = __DIR__ . '/../' . $imagePath;
        }

        if ($imagePath === null) {
            $shoeStmt = $conn->prepare(
                "UPDATE shoe
                SET brand_id = ?, category_id = ?, shoe_name = ?, gender_target_id = ?
                 WHERE shoe_id = ?"
            );
            $shoeStmt->bind_param(
                "iisii",
                $_POST['brand_id'],
                $_POST['category_id'],
                $_POST['shoe_name'],
                $_POST['gender_target_id'],
                $_POST['shoe_id']
            );
        } else {
            $shoeStmt = $conn->prepare(
                "UPDATE shoe
                SET brand_id = ?, category_id = ?, shoe_name = ?, gender_target_id = ?, image_path = ?
                 WHERE shoe_id = ?"
            );
            $shoeStmt->bind_param(
                "iisisi",
                $_POST['brand_id'],
                $_POST['category_id'],
                $_POST['shoe_name'],
                $_POST['gender_target_id'],
                $imagePath,
                $_POST['shoe_id']
            );
        }
        $shoeStmt->execute();

        $variantStmt = $conn->prepare(
            "UPDATE shoe_variant
             SET shoe_price = ?, size_id = ?, color_id = ?, quantity = ?, status_id = ?
             WHERE shoe_variant_id = ?"
        );
        $variantStmt->bind_param(
            "diiiii",
            $_POST['shoe_price'],
            $_POST['size_id'],
            $_POST['color_id'],
            $_POST['quantity'],
            $_POST['status_id'],
            $shoeVariantId
        );
        $variantStmt->execute();

        $conn->commit();

        if (
            $imagePath !== null &&
            $oldImagePath !== null &&
            $oldImagePath !== 'images/logo.png' &&
            str_starts_with($oldImagePath, 'images/')
        ) {
            $oldTarget = __DIR__ . '/../' . $oldImagePath;
            if (is_file($oldTarget)) {
                unlink($oldTarget);
            }
        }

        header("Location: /two-step/staff/staff_inventory.php?updated=1");
        exit;
    } catch (RuntimeException $ex) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        if ($savedImagePath !== null && is_file($savedImagePath)) {
            unlink($savedImagePath);
        }
        $message = $ex->getMessage();
    } catch (mysqli_sql_exception $ex) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        if ($savedImagePath !== null && is_file($savedImagePath)) {
            unlink($savedImagePath);
        }
        $message = 'Could not save the shoe details.';
    }
}

$sql = "SELECT
            sv.shoe_variant_id,
            sv.shoe_id,
            sv.size_id,
            sv.color_id,
            sv.quantity,
            sv.status_id,
            sv.shoe_price,
            s.brand_id,
            s.category_id,
            s.shoe_name,
            s.gender_target_id,
            s.image_path
        FROM shoe_variant AS sv
        JOIN shoe AS s ON s.shoe_id = sv.shoe_id
        WHERE sv.shoe_variant_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shoeVariantId);
$stmt->execute();
$shoe = $stmt->get_result()->fetch_assoc();

if (!$shoe) {
    header("Location: /two-step/staff/staff_inventory.php");
    exit;
}

function options($conn, $table, $idColumn, $labelColumn) {
    $allowedTables = [
        'brand' => ['id' => 'brand_id', 'label' => 'brand_name'],
        'category' => ['id' => 'category_id', 'label' => 'category_name'],
        'gender_target' => ['id' => 'gender_target_id', 'label' => 'gender'],
        'size' => ['id' => 'size_id', 'label' => 'size_uk'],
        'color' => ['id' => 'color_id', 'label' => 'color_name'],
        'status' => ['id' => 'status_id', 'label' => 'status'],
    ];

    if (!isset($allowedTables[$table]) || $allowedTables[$table]['id'] !== $idColumn || $allowedTables[$table]['label'] !== $labelColumn) {
        return [];
    }

    $result = $conn->query("SELECT `$idColumn`, `$labelColumn` FROM `$table` ORDER BY `$labelColumn` ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$brands = options($conn, 'brand', 'brand_id', 'brand_name');
$categories = options($conn, 'category', 'category_id', 'category_name');
$genders = options($conn, 'gender_target', 'gender_target_id', 'gender');
$sizes = options($conn, 'size', 'size_id', 'size_uk');
$colors = options($conn, 'color', 'color_id', 'color_name');
$statuses = options($conn, 'status', 'status_id', 'status');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Shoe Inventory</title>
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
        <div class="panel inventory-panel manage-panel">
            <h2>Manage Shoe</h2>

            <?php if ($message): ?>
                <div class="notice"><?= e($message) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="manage-form">
                <input type="hidden" name="shoe_variant_id" value="<?= (int)$shoeVariantId ?>">
                <input type="hidden" name="shoe_id" value="<?= (int)$shoe['shoe_id'] ?>">

                <label for="brand_id">Brand</label>
                <select id="brand_id" name="brand_id" required>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)$brand['brand_id'] ?>" <?= (int)$brand['brand_id'] === (int)$shoe['brand_id'] ? 'selected' : '' ?>>
                            <?= e($brand['brand_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="shoe_name">Shoe Name</label>
                <input id="shoe_name" type="text" name="shoe_name" value="<?= e($shoe['shoe_name']) ?>" required>

                <label for="shoe_price">Variant Price (RM)</label>
                <input id="shoe_price" type="number" name="shoe_price" value="<?= e($shoe['shoe_price']) ?>" step="0.01" min="0" required>

                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['category_id'] ?>" <?= (int)$category['category_id'] === (int)$shoe['category_id'] ? 'selected' : '' ?>>
                            <?= e($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="gender_target_id">Gender Target</label>
                <select id="gender_target_id" name="gender_target_id" required>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?= (int)$gender['gender_target_id'] ?>" <?= (int)$gender['gender_target_id'] === (int)$shoe['gender_target_id'] ? 'selected' : '' ?>>
                            <?= e($gender['gender']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="size_id">Size (UK)</label>
                <select id="size_id" name="size_id" required>
                    <?php foreach ($sizes as $size): ?>
                        <option value="<?= (int)$size['size_id'] ?>" <?= (int)$size['size_id'] === (int)$shoe['size_id'] ? 'selected' : '' ?>>
                            UK <?= e($size['size_uk']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="color_id">Color</label>
                <select id="color_id" name="color_id" required>
                    <?php foreach ($colors as $color): ?>
                        <option value="<?= (int)$color['color_id'] ?>" <?= (int)$color['color_id'] === (int)$shoe['color_id'] ? 'selected' : '' ?>>
                            <?= e($color['color_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="quantity">Quantity</label>
                <input id="quantity" type="number" name="quantity" value="<?= (int)$shoe['quantity'] ?>" min="0" required>

                <label for="status_id">Status</label>
                <select id="status_id" name="status_id" required>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= (int)$status['status_id'] ?>" <?= (int)$status['status_id'] === (int)$shoe['status_id'] ? 'selected' : '' ?>>
                            <?= e($status['status']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="image">Replace Image</label>
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                <?php if (!empty($shoe['image_path'])): ?>
                    <img class="current-shoe-image" src="<?= e($shoe['image_path']) ?>" alt="<?= e($shoe['shoe_name']) ?>">
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="save-btn">Save Changes</button>
                    <a href="staff/staff_inventory.php" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
