<?php
require_once __DIR__ . '/../config/db.php';

$sql = "
    SELECT shoe_id, shoe_name, shoe_price, image_path, description
    FROM shoe
    ORDER BY shoe_id
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
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>
        <nav>
            <a href="index.html"><strong>Home</strong></a> |
            <a href="customer/browse.php"><strong>Browse</strong></a> |
            <a href="customer/cart/cart.php"><strong>Cart</strong></a> |
            <a href="auth/login.html"><strong>Logout</strong></a>
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
                    <h3><?= htmlspecialchars($shoe['shoe_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($shoe['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>RM<?= number_format((float) $shoe['shoe_price'], 2) ?></strong></p>

                    <form action="customer/cart/add_to_cart.php" method="POST">
                        <input type="hidden" name="shoe_id" value="<?= (int) $shoe['shoe_id'] ?>">
                        <button type="submit" class="submit-button">Buy Now</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>
