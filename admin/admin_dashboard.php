<?php
require_once __DIR__ . '/../config/authorization.php';
require_role(1);
require_once __DIR__ . '/../config/db.php';

function admin_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$accountGroups = [
    'staff' => ['label' => 'Staff', 'role_id' => 2, 'accounts' => []],
    'customer' => ['label' => 'Customer', 'role_id' => 3, 'accounts' => []],
];

foreach ($accountGroups as &$group) {
    $stmt = $conn->prepare(
        'SELECT user_id, username, fullname, join_date, leave_date
         FROM user WHERE role_id = ? ORDER BY username'
    );
    $stmt->bind_param('i', $group['role_id']);
    $stmt->execute();
    $group['accounts'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
unset($group);
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
    <link rel="stylesheet" href="site-nav.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="2 Step Sneakers" class="logo">
        </div>

        <nav>
            <a href="admin/admin_dashboard.php"><strong>Dashboard</strong></a> |
            <a href="admin/account_management.php?type=staff"><strong>Staff</strong></a> |
            <a href="admin/account_management.php?type=customer"><strong>Customer</strong></a> |
            <a href="/phpmyadmin/" target="_blank" rel="noopener"><strong>phpMyAdmin</strong></a> |
            <a href="auth/logout.php"><strong>Log Out</strong></a>
        </nav>
    </header>

    <main class="account-management">
        <h1>Account Overview</h1>

        <?php foreach ($accountGroups as $type => $group): ?>
            <section class="staff-container">
                <h2><?= admin_e($group['label']) ?> Accounts</h2>
                <div class="account-table-wrap">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full name</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($group['accounts']): ?>
                                <?php foreach ($group['accounts'] as $account): ?>
                                    <tr>
                                        <td><?= (int)$account['user_id'] ?></td>
                                        <td><?= admin_e($account['username']) ?></td>
                                        <td><?= admin_e($account['fullname'] ?? '') ?></td>
                                        <td><?= $account['join_date'] ? admin_e(date('d M Y', strtotime($account['join_date']))) : 'Not set' ?></td>
                                        <td><?= $account['leave_date'] === null ? 'Active' : 'Deactivated' ?></td>
                                        <td>
                                            <a class="manage-btn" href="admin/account_management.php?type=<?= admin_e($type) ?>&amp;edit=<?= (int)$account['user_id'] ?>">Manage</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-accounts">No <?= admin_e(strtolower($group['label'])) ?> accounts found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>
    </main>
</body>
</html>