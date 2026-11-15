<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Ambil ID kasut dari URL
$shoe_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM shoe WHERE shoe_id = ?");
$stmt->bind_param("i", $shoe_id);
$stmt->execute();
$result = $stmt->get_result();
$shoe = $result->fetch_assoc();

if (!$shoe) {
    die("Shoe not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($shoe['shoe_name']) ?> - Details</title>
    <base href="/two-step/">
    <link rel="stylesheet" href="customer/browse.css">
</head>
<body>
    <div style="max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <img src="<?= htmlspecialchars($shoe['image_path']) ?>" alt="" style="width: 100%; border-radius: 10px;">
        <h2><?= htmlspecialchars($shoe['shoe_name']) ?></h2>
        <p><?= htmlspecialchars($shoe['description']) ?></p>
        <p><strong>RM<?= number_format($shoe['shoe_price'], 2) ?></strong></p>

        <form action="customer/cart/add_to_cart.php" method="POST">
            <input type="hidden" name="shoe_id" value="<?= $shoe['shoe_id'] ?>">

            <label><b>Please Choose Size (UK):</b></label><br>
            <select name="size" required style="width: 100%; padding: 8px; margin: 10px 0;">
                <option value="">-- Please Choose Size --</option>
                <option value="7">UK 7</option>
                <option value="8">UK 8</option>
                <option value="9">UK 9</option>
                <option value="10">UK 10</option>
            </select>
            <br>

            <label><b>Please Choose Color:</b></label><br>
            <select name="color" required style="width: 100%; padding: 8px; margin: 10px 0;">
                <option value="">-- Please Choose Color --</option>
                <option value="Black">Black</option>
                <option value="White">White</option>
                <option value="Grey">Grey</option>
            </select>
            <br>

            <button type="submit" style="background: black; color: white; padding: 10px 15px; border: none; width: 100%; border-radius: 5px; cursor: pointer;">Add to Cart</button>
        </form>
        <br>
        <a href="customer/browse.php">← Back to Browse</a>
    </div>
</body>
</html>