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

$sql_variants = "SELECT shoe_variant.shoe_variant_id, size.size_uk, color.color_name 
                 FROM shoe_variant
                 JOIN size ON shoe_variant.size_id = size.size_id
                 JOIN color ON shoe_variant.color_id = color.color_id
                 WHERE shoe_variant.shoe_id = ?";
$stmt_var = $conn->prepare($sql_variants);
$stmt_var->bind_param("i", $shoe_id);
$stmt_var->execute();
$variants_result = $stmt_var->get_result();

$variants = [];
while ($row = $variants_result->fetch_assoc()) {
    $variants[] = $row;
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
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="customer/browse.php"><img src="images/logo.png" alt="2 Step Sneakers Logo" class="logo"></a>
        </div>
        <nav>
            <a href="customer/browse.php"><strong>Browse</strong></a> |
            <a href="customer/cart/cart.php"><strong>Cart</strong></a> |
            <a href="customer/account/logout.php"><strong>Logout</strong></a>
        </nav>
    </header>


    <div style="max-width: 300px; margin: 50px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        
        <img src="<?= htmlspecialchars($shoe['image_path']) ?>" alt="<?= htmlspecialchars($shoe['shoe_name']) ?>" style="width: 100%; border-radius: 10px;">
        <h2><?= htmlspecialchars($shoe['shoe_name']) ?></h2>
        <p><?= htmlspecialchars($shoe['description']) ?></p>
        <p><strong>RM<?= number_format($shoe['shoe_price'], 2) ?></strong></p>

        <?php if (!empty($variants)): ?>
            <form action="customer/cart/add_to_cart.php" method="POST">
                <label><b>Choose Size & Color:</b></label><br>
                <select name="shoe_variant_id" required style="width: 100%; padding: 8px; margin: 10px 0;">
                    <option value="">-- Please Choose Variant (Size & Color) --</option>
                    <?php foreach ($variants as $var): ?>
                        <option value="<?= $var['shoe_variant_id']; ?>">
                            Size: <?= htmlspecialchars($var['size_uk']); ?> | Color: <?= htmlspecialchars($var['color_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br>

                <button type="submit" style="background: black; color: white; padding: 10px 15px; border: none; width: 100%; border-radius: 5px; cursor: pointer;">Add to Cart</button>
            </form>
        <?php else: ?>
            <p style="color: #b91c1c; font-weight: bold; margin: 15px 0;">This product is currently unavailable.</p>
        <?php endif; ?>

        <br>
        <a href="customer/browse.php" style="background: black; color: white; padding: 10px 15px; border: none; width: 100%; border-radius: 5px; text-decoration: none;">Back to Browse</a>
    </div>
</body>
</html>