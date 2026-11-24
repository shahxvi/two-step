<?php
require_once __DIR__ . '/../config/db.php';

$sql = "SELECT * FROM user WHERE role_id = 2";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <base href="/two-step/">
    <link rel="icon" type="image/jpg" href="images/logo.png">
    <link rel="stylesheet" href="admin/admin_dashboard.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>

        <nav>
            <a><strong>Dashboard</strong></a> |
            <a><strong>Staff</strong></a> |
            <a><strong>Customer</strong></a> |
            <a href="/phpmyadmin/" target="_blank" rel="noopener"><strong>phpMyAdmin</strong></a> |
            <a href="auth/login/login.html"><strong>Log Out</strong></a>
        </nav>
    </header>

    <h1 style="padding: 0px 20px;">Staff Members</h1>

    <div class="staff-container">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Role ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0) : ?>
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['role_id']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">No staff members found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>