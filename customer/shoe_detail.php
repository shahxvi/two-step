<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$shoe_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt_shoe = $conn->prepare("SELECT * FROM shoe WHERE shoe_id = ?");
$stmt_shoe->bind_param("i", $shoe_id);
$stmt_shoe->execute();
$shoe = $stmt_shoe->get_result()->fetch_assoc();

if (!$shoe) {
    die("Shoe not found.");
}

$sql_variants = "SELECT
                    sv.shoe_variant_id,
                    sv.shoe_price,
                    sv.quantity,
                    sv.status_id,
                    sz.size_id,
                    sz.size_uk,
                    c.color_id,
                    c.color_name
                 FROM shoe_variant AS sv
                 JOIN size AS sz ON sv.size_id = sz.size_id
                 JOIN color AS c ON sv.color_id = c.color_id
                 WHERE sv.shoe_id = ?
                 ORDER BY sz.size_uk, c.color_name";
$stmt_var = $conn->prepare($sql_variants);
$stmt_var->bind_param("i", $shoe_id);
$stmt_var->execute();
$variants_result = $stmt_var->get_result();

$variants = [];
$sizeOptions = [];
$colorOptions = [];
while ($row = $variants_result->fetch_assoc()) {
    $variants[] = $row;
    $sizeOptions[$row['size_id']] = $row['size_uk'];
    $colorOptions[$row['color_id']] = $row['color_name'];
}
$stmt_var->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2 Step Sneakers - Shoe Details</title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png"/>
    <link rel="stylesheet" href="customer/browse.css">
    <link rel="stylesheet" href="site-nav.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="customer/browse.php"><img src="images/logo.png" alt="2 Step Sneakers Logo" class="logo"></a>
        </div>
        <nav>
            <a href="customer/browse.php"><strong>Browse</strong></a> |
            <a href="customer/cart/cart.php"><strong>Cart</strong></a> |
            <a href="auth/logout.php"><strong>Logout</strong></a>
        </nav>
    </header>


    <div style="max-width: 300px; margin: 50px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        
        <img src="<?= htmlspecialchars($shoe['image_path']) ?>" alt="<?= htmlspecialchars($shoe['shoe_name']) ?>" style="width: 100%; border-radius: 10px;">
        <h2><?= htmlspecialchars($shoe['shoe_name']) ?></h2>
        <p><?= htmlspecialchars($shoe['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($variants)): ?>
            <form action="customer/cart/add_to_cart.php" method="POST">
                <label for="sizeSelect"><b>Choose Size (UK):</b></label>
                <select id="sizeSelect" required style="width: 100%; padding: 8px; margin: 10px 0;">
                    <option value="">-- Choose size --</option>
                    <?php foreach ($sizeOptions as $sizeId => $sizeUk): ?>
                        <option value="<?= (int)$sizeId ?>"><?= htmlspecialchars($sizeUk, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="colorSelect"><b>Choose Color:</b></label>
                <select id="colorSelect" required style="width: 100%; padding: 8px; margin: 10px 0;">
                    <option value="">-- Choose color --</option>
                    <?php foreach ($colorOptions as $colorId => $colorName): ?>
                        <option value="<?= (int)$colorId ?>"><?= htmlspecialchars($colorName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>

                <p><strong id="variantPrice" aria-live="polite">Choose a size and color to see the price.</strong></p>
                <p id="variantAvailability" aria-live="polite"></p>
                <input type="hidden" name="shoe_variant_id" id="shoeVariantId">
                <button id="addToCartButton" type="submit" disabled style="background: black; color: white; padding: 10px 15px; border: none; width: 100%; border-radius: 5px; cursor: pointer;">Add to Cart</button>
            </form>
        <?php else: ?>
            <p style="color: #b91c1c; font-weight: bold; margin: 15px 0;">This product is currently unavailable.</p>
        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === 'out_of_stock'): ?>
            <p role="alert" style="color: #b91c1c;">That size and color is out of stock.</p>
        <?php endif; ?>

        <br>
        <a href="customer/browse.php" style="background: black; color: white; padding: 10px 15px; border: none; width: 100%; border-radius: 5px; text-decoration: none;">Back to Browse</a>
    </div>
    <script>
        const variants = <?= json_encode(
            $variants,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;
        const sizeSelect = document.getElementById('sizeSelect');
        const colorSelect = document.getElementById('colorSelect');
        const variantId = document.getElementById('shoeVariantId');
        const variantPrice = document.getElementById('variantPrice');
        const variantAvailability = document.getElementById('variantAvailability');
        const addToCartButton = document.getElementById('addToCartButton');

        function updateVariantSelection() {
            const selectedVariant = variants.find(variant =>
                String(variant.size_id) === sizeSelect.value &&
                String(variant.color_id) === colorSelect.value
            );

            variantId.value = selectedVariant ? selectedVariant.shoe_variant_id : '';
            addToCartButton.disabled = !selectedVariant ||
                Number(selectedVariant.quantity) < 1 ||
                Number(selectedVariant.status_id) !== 1;

            if (!selectedVariant) {
                variantPrice.textContent = 'This size and color combination is unavailable.';
                variantAvailability.textContent = '';
                return;
            }

            variantPrice.textContent = `RM${Number(selectedVariant.shoe_price).toFixed(2)}`;
            variantAvailability.textContent = addToCartButton.disabled ? 'Out of stock' : 'In stock';
        }

        sizeSelect.addEventListener('change', updateVariantSelection);
        colorSelect.addEventListener('change', updateVariantSelection);
    </script>
</body>
</html>