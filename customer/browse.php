<?php
require_once __DIR__ . '/../config/db.php';

$sql = "
    SELECT s.shoe_id, s.shoe_name, MIN(sv.shoe_price) AS starting_price, s.image_path, b.brand_name
    FROM shoe AS s
    JOIN brand AS b ON b.brand_id = s.brand_id
    LEFT JOIN shoe_variant AS sv ON sv.shoe_id = s.shoe_id
    GROUP BY s.shoe_id, s.shoe_name, s.image_path, b.brand_name
    ORDER BY s.shoe_id
";
$result = $conn->query($sql);

if ($result === false) {
    http_response_code(500);
    exit('Unable to load shoes.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Step Sneakers: Browsing</title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="customer/browse.css">
    <link rel="stylesheet" href="site-nav.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>
        <nav>
            <a href="customer/browse.php"><strong>Browse</strong></a> |
            <a href="customer/cart/cart.php"><strong>Cart</strong></a> |
            <a href="auth/logout.php"><strong>Logout</strong></a>
        </nav>
    </header>
    <main>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 25px; justify-content: center;">
            <!-- Loop through the shoes from db and display them into grid cards -->
            <?php while ($shoe = $result->fetch_assoc()): ?>
                <div style="background: white; padding: 10px; border-radius: 10px; width: 200px; text-align: center; box-shadow: 0px 2px 5px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                    <img
                        src="<?= htmlspecialchars($shoe['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($shoe['shoe_name'], ENT_QUOTES, 'UTF-8') ?>"
                        style="width: 100%; border-radius: 10px;"
                    >
                    <h3><?= htmlspecialchars($shoe['brand_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($shoe['shoe_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong><?= $shoe['starting_price'] !== null ? 'From RM' . number_format((float)$shoe['starting_price'], 2) : 'Unavailable' ?></strong></p>

                    <a href="customer/shoe_detail.php?id=<?= (int) $shoe['shoe_id'] ?>" style="display: inline-block; background: black; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; margin-top: auto;">Select Options</a>
                </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>