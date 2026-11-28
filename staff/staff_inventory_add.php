<?php
session_start();
require_once __DIR__ . '/../config/authorization.php';
require_role(2);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/shoe_image_upload.php';

function e($text): string
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function load_options(mysqli $conn, string $query): array
{
    $result = $conn->query($query);
    if (!$result) {
        die('Could not load shoe options: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function has_option(array $options, string $idColumn, int $id): bool
{
    foreach ($options as $option) {
        if ((int)$option[$idColumn] === $id) {
            return true;
        }
    }
    return false;
}

function create_named_option(mysqli $conn, string $type, string $name): int
{
    $lookups = [
        'brand' => ['table' => 'brand', 'id' => 'brand_id', 'name' => 'brand_name'],
        'category' => ['table' => 'category', 'id' => 'category_id', 'name' => 'category_name'],
        'color' => ['table' => 'color', 'id' => 'color_id', 'name' => 'color_name'],
    ];
    $lookup = $lookups[$type] ?? null;
    if ($lookup === null) {
        throw new LogicException('Unsupported lookup type.');
    }

    $stmt = $conn->prepare(
        "SELECT `{$lookup['id']}` FROM `{$lookup['table']}` WHERE `{$lookup['name']}` = ? LIMIT 1"
    );
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($existing) {
        return (int)$existing[$lookup['id']];
    }

    $hasBrandCountry = false;
    if ($type === 'brand') {
        $columns = $conn->query("SHOW COLUMNS FROM brand LIKE 'brand_country'");
        if (!$columns) {
            throw new RuntimeException('Could not inspect the brand fields.');
        }
        $hasBrandCountry = $columns->num_rows > 0;
    }

    $insertSql = $hasBrandCountry
        ? 'INSERT INTO brand (brand_name, brand_country) VALUES (?, 0)'
        : "INSERT INTO `{$lookup['table']}` (`{$lookup['name']}`) VALUES (?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->close();
    return (int)$conn->insert_id;
}

function create_size_option(mysqli $conn, int $cm, int $us, int $uk, int $eu): int
{
    $stmt = $conn->prepare('SELECT size_id FROM size WHERE size_uk = ? LIMIT 1');
    $stmt->bind_param('i', $uk);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($existing) {
        return (int)$existing['size_id'];
    }

    $stmt = $conn->prepare(
        'INSERT INTO size (size_cm, size_us, size_uk, size_eu) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('iiii', $cm, $us, $uk, $eu);
    $stmt->execute();
    $stmt->close();
    return (int)$conn->insert_id;
}

function post_int(string $key)
{
    $value = $_POST[$key] ?? null;
    return is_scalar($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$brands = load_options($conn, 'SELECT brand_id, brand_name FROM brand ORDER BY brand_name');
$categories = load_options($conn, 'SELECT category_id, category_name FROM category ORDER BY category_name');
$genders = load_options($conn, 'SELECT gender_target_id, gender FROM gender_target ORDER BY gender');
$sizes = load_options($conn, 'SELECT size_id, size_uk FROM size ORDER BY size_uk');
$colors = load_options($conn, 'SELECT color_id, color_name FROM color ORDER BY color_name');

$form = [
    'brand_id' => '',
    'new_brand_name' => '',
    'shoe_name' => '',
    'category_id' => '',
    'new_category_name' => '',
    'gender_target_id' => '',
    'size_id' => '',
    'new_size_cm' => '',
    'new_size_us' => '',
    'new_size_uk' => '',
    'new_size_eu' => '',
    'color_id' => '',
    'new_color_name' => '',
    'shoe_price' => '',
    'quantity' => '1',
];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $key) {
        $form[$key] = post_string($key);
    }

    $brandSelection = $form['brand_id'];
    $categorySelection = $form['category_id'];
    $genderTargetId = post_int('gender_target_id');
    $sizeSelection = $form['size_id'];
    $colorSelection = $form['color_id'];
    $quantity = post_int('quantity');
    $newSizeCm = post_int('new_size_cm');
    $newSizeUs = post_int('new_size_us');
    $newSizeUk = post_int('new_size_uk');
    $newSizeEu = post_int('new_size_eu');
    $priceValue = $_POST['shoe_price'] ?? null;
    $price = is_scalar($priceValue) ? filter_var($priceValue, FILTER_VALIDATE_FLOAT) : false;
    $shoeName = $form['shoe_name'];
    $brandIsNew = $brandSelection === 'new';
    $categoryIsNew = $categorySelection === 'new';
    $sizeIsNew = $sizeSelection === 'new';
    $colorIsNew = $colorSelection === 'new';

    $brandId = filter_var($brandSelection, FILTER_VALIDATE_INT);
    $categoryId = filter_var($categorySelection, FILTER_VALIDATE_INT);
    $sizeId = filter_var($sizeSelection, FILTER_VALIDATE_INT);
    $colorId = filter_var($colorSelection, FILTER_VALIDATE_INT);

    $brandValid = $brandIsNew
        ? $form['new_brand_name'] !== '' && strlen($form['new_brand_name']) <= 255
        : $brandId !== false && $brandId > 0 && has_option($brands, 'brand_id', $brandId);
    $categoryValid = $categoryIsNew
        ? $form['new_category_name'] !== '' && strlen($form['new_category_name']) <= 255
        : $categoryId !== false && $categoryId > 0 && has_option($categories, 'category_id', $categoryId);
    $sizeValid = $sizeIsNew
        ? $newSizeCm !== false && $newSizeCm > 0 &&
            $newSizeUs !== false && $newSizeUs > 0 &&
            $newSizeUk !== false && $newSizeUk > 0 &&
            $newSizeEu !== false && $newSizeEu > 0
        : $sizeId !== false && $sizeId > 0 && has_option($sizes, 'size_id', $sizeId);
    $colorValid = $colorIsNew
        ? $form['new_color_name'] !== '' && strlen($form['new_color_name']) <= 255
        : $colorId !== false && $colorId > 0 && has_option($colors, 'color_id', $colorId);

    if (
        $shoeName === '' ||
        strlen($shoeName) > 255 ||
        !$brandValid ||
        !$categoryValid ||
        $genderTargetId === false || $genderTargetId < 1 || !has_option($genders, 'gender_target_id', $genderTargetId) ||
        !$sizeValid ||
        !$colorValid ||
        $quantity === false || $quantity < 0 ||
        $price === false || $price < 0
    ) {
        $message = 'Choose an existing option or provide valid details for each new brand, category, size, or color.';
    } else {
        $transactionStarted = false;
        $savedImagePath = null;

        try {
            $imagePath = 'images/logo.png';
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $imagePath = upload_shoe_image($_FILES['image']);
                $savedImagePath = __DIR__ . '/../' . $imagePath;
            }

            $conn->begin_transaction();
            $transactionStarted = true;

            $brandId = $brandIsNew
                ? create_named_option($conn, 'brand', $form['new_brand_name'])
                : (int)$brandId;
            $categoryId = $categoryIsNew
                ? create_named_option($conn, 'category', $form['new_category_name'])
                : (int)$categoryId;
            $sizeId = $sizeIsNew
                ? create_size_option($conn, $newSizeCm, $newSizeUs, $newSizeUk, $newSizeEu)
                : (int)$sizeId;
            $colorId = $colorIsNew
                ? create_named_option($conn, 'color', $form['new_color_name'])
                : (int)$colorId;

            $shoeStmt = $conn->prepare(
                'INSERT INTO shoe (brand_id, category_id, shoe_name, gender_target_id, image_path)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $shoeStmt->bind_param('iisis', $brandId, $categoryId, $shoeName, $genderTargetId, $imagePath);
            $shoeStmt->execute();
            $shoeId = $conn->insert_id;
            $shoeStmt->close();

            $statusId = $quantity > 0 ? 1 : 2;
            $variantStmt = $conn->prepare(
                'INSERT INTO shoe_variant (shoe_id, shoe_price, size_id, color_id, quantity, status_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $variantStmt->bind_param('idiiii', $shoeId, $price, $sizeId, $colorId, $quantity, $statusId);
            $variantStmt->execute();
            $variantStmt->close();

            $conn->commit();
            header('Location: /two-step/staff/staff_inventory.php?added=1');
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
            $message = 'Could not add the shoe. Please check the details and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Shoe Inventory</title>
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
            <h2>Add a Shoe</h2>
            <p>Add the shoe details and its first size/color variant.</p>

            <?php if ($message !== ''): ?>
                <div class="notice"><?= e($message) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="manage-form">
                <label for="brand_id">Brand</label>
                <select id="brand_id" name="brand_id" required data-new-fields="new-brand-fields">
                    <option value="">Select brand</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)$brand['brand_id'] ?>" <?= (string)$brand['brand_id'] === $form['brand_id'] ? 'selected' : '' ?>>
                            <?= e($brand['brand_name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="new" <?= $form['brand_id'] === 'new' ? 'selected' : '' ?>>Add new brand...</option>
                </select>
                <div id="new-brand-fields" class="new-option-fields" hidden>
                    <label for="new_brand_name">New Brand Name</label>
                    <input id="new_brand_name" type="text" name="new_brand_name" maxlength="255" value="<?= e($form['new_brand_name']) ?>" data-required-when-new>
                </div>

                <label for="shoe_name">Shoe Name</label>
                <input id="shoe_name" type="text" name="shoe_name" maxlength="255" value="<?= e($form['shoe_name']) ?>" required>

                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required data-new-fields="new-category-fields">
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['category_id'] ?>" <?= (string)$category['category_id'] === $form['category_id'] ? 'selected' : '' ?>>
                            <?= e($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="new" <?= $form['category_id'] === 'new' ? 'selected' : '' ?>>Add new category...</option>
                </select>
                <div id="new-category-fields" class="new-option-fields" hidden>
                    <label for="new_category_name">New Category Name</label>
                    <input id="new_category_name" type="text" name="new_category_name" maxlength="255" value="<?= e($form['new_category_name']) ?>" data-required-when-new>
                </div>

                <label for="gender_target_id">Gender Target</label>
                <select id="gender_target_id" name="gender_target_id" required>
                    <option value="">Select gender target</option>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?= (int)$gender['gender_target_id'] ?>" <?= (string)$gender['gender_target_id'] === $form['gender_target_id'] ? 'selected' : '' ?>>
                            <?= e($gender['gender']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="size_id">First Size (UK)</label>
                <select id="size_id" name="size_id" required data-new-fields="new-size-fields">
                    <option value="">Select size</option>
                    <?php foreach ($sizes as $size): ?>
                        <option value="<?= (int)$size['size_id'] ?>" <?= (string)$size['size_id'] === $form['size_id'] ? 'selected' : '' ?>>
                            UK <?= e($size['size_uk']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="new" <?= $form['size_id'] === 'new' ? 'selected' : '' ?>>Add new size...</option>
                </select>
                <div id="new-size-fields" class="new-option-fields" hidden>
                    <label for="new_size_uk">New UK Size</label>
                    <input id="new_size_uk" type="number" name="new_size_uk" min="1" step="1" value="<?= e($form['new_size_uk']) ?>" data-required-when-new>
                    <label for="new_size_cm">Size (cm)</label>
                    <input id="new_size_cm" type="number" name="new_size_cm" min="1" step="1" value="<?= e($form['new_size_cm']) ?>" data-required-when-new>
                    <label for="new_size_us">Size (US)</label>
                    <input id="new_size_us" type="number" name="new_size_us" min="1" step="1" value="<?= e($form['new_size_us']) ?>" data-required-when-new>
                    <label for="new_size_eu">Size (EU)</label>
                    <input id="new_size_eu" type="number" name="new_size_eu" min="1" step="1" value="<?= e($form['new_size_eu']) ?>" data-required-when-new>
                </div>

                <label for="color_id">First Color</label>
                <select id="color_id" name="color_id" required data-new-fields="new-color-fields">
                    <option value="">Select color</option>
                    <?php foreach ($colors as $color): ?>
                        <option value="<?= (int)$color['color_id'] ?>" <?= (string)$color['color_id'] === $form['color_id'] ? 'selected' : '' ?>>
                            <?= e($color['color_name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="new" <?= $form['color_id'] === 'new' ? 'selected' : '' ?>>Add new color...</option>
                </select>
                <div id="new-color-fields" class="new-option-fields" hidden>
                    <label for="new_color_name">New Color Name</label>
                    <input id="new_color_name" type="text" name="new_color_name" maxlength="255" value="<?= e($form['new_color_name']) ?>" data-required-when-new>
                </div>

                <label for="shoe_price">Variant Price (RM)</label>
                <input id="shoe_price" type="number" name="shoe_price" min="0" step="0.01" value="<?= e($form['shoe_price']) ?>" required>

                <label for="quantity">Initial Quantity</label>
                <input id="quantity" type="number" name="quantity" min="0" value="<?= e($form['quantity']) ?>" required>

                <label for="image">Shoe Image (optional)</label>
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">

                <div class="form-actions">
                    <button type="submit" class="save-btn">Add Shoe</button>
                    <a href="staff/staff_inventory.php" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    <script>
        document.querySelectorAll('select[data-new-fields]').forEach((select) => {
            const fields = document.getElementById(select.dataset.newFields);
            const updateFields = () => {
                const isNew = select.value === 'new';
                fields.hidden = !isNew;
                fields.querySelectorAll('[data-required-when-new]').forEach((input) => {
                    input.required = isNew;
                });
            };

            select.addEventListener('change', updateFields);
            updateFields();
        });
    </script>
</body>
</html>
