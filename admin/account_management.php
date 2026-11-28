<?php
session_start();
require_once __DIR__ . '/../config/authorization.php';
require_role(1);
require_once __DIR__ . '/../config/db.php';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function username_exists(mysqli $conn, string $username, int $excludeUserId): bool
{
    $stmt = $conn->prepare('SELECT user_id FROM user WHERE username = ? AND user_id <> ? LIMIT 1');
    $stmt->bind_param('si', $username, $excludeUserId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

$accountTypes = [
    'staff' => ['role_id' => 2, 'label' => 'Staff'],
    'customer' => ['role_id' => 3, 'label' => 'Customer'],
];
$requestedType = $_POST['type'] ?? $_GET['type'] ?? 'staff';
$type = is_string($requestedType) ? $requestedType : '';

if (!isset($accountTypes[$type])) {
    http_response_code(400);
    exit('Invalid account type.');
}

$roleId = $accountTypes[$type]['role_id'];
$accountLabel = $accountTypes[$type]['label'];
$csrfToken = $_SESSION['admin_account_csrf'] ?? '';
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['admin_account_csrf'] = $csrfToken;
}

$form = [
    'user_id' => '',
    'username' => '',
    'fullname' => '',
];
$editingIsActive = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId === false || $editId < 1) {
        http_response_code(400);
        exit('Invalid account ID.');
    }

    $stmt = $conn->prepare('SELECT user_id, username, fullname, leave_date FROM user WHERE user_id = ? AND role_id = ?');
    $stmt->bind_param('ii', $editId, $roleId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$account) {
        http_response_code(404);
        exit('Account not found.');
    }

    $form = [
        'user_id' => (string)$account['user_id'],
        'username' => $account['username'],
        'fullname' => $account['fullname'] ?? '',
    ];
    $editingIsActive = $account['leave_date'] === null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        http_response_code(403);
        exit('Invalid form token.');
    }

    $action = post_string('action');

    if ($action === 'save') {
        $form['user_id'] = post_string('user_id');
        $form['username'] = post_string('username');
        $form['fullname'] = post_string('fullname');
        $passwordValue = $_POST['password'] ?? '';
        $password = is_string($passwordValue) ? $passwordValue : '';

        $isEditing = $form['user_id'] !== '';
        $userId = $isEditing ? filter_var($form['user_id'], FILTER_VALIDATE_INT) : 0;
        $fullName = $form['fullname'] === '' ? null : $form['fullname'];

        if (
            $form['username'] === '' ||
            strlen($form['username']) > 255 ||
            strlen($form['fullname']) > 255 ||
            ($isEditing && ($userId === false || $userId < 1)) ||
            (!$isEditing && (strlen($password) < 6 || strlen($password) > 255)) ||
            ($isEditing && $password !== '' && (strlen($password) < 6 || strlen($password) > 255))
        ) {
            $message = 'Enter a valid username and full name. New passwords must be 6 to 255 characters.';
        } else {
            $transactionStarted = false;
            try {
                $conn->begin_transaction();
                $transactionStarted = true;

                $excludeUserId = $isEditing ? (int)$userId : 0;
                if (username_exists($conn, $form['username'], $excludeUserId)) {
                    throw new RuntimeException('That username is already in use.');
                }

                if ($isEditing) {
                    $check = $conn->prepare('SELECT user_id FROM user WHERE user_id = ? AND role_id = ?');
                    $check->bind_param('ii', $userId, $roleId);
                    $check->execute();
                    $accountExists = $check->get_result()->num_rows > 0;
                    $check->close();
                    if (!$accountExists) {
                        throw new RuntimeException('Account not found.');
                    }

                    if ($password === '') {
                        $stmt = $conn->prepare(
                            'UPDATE user SET username = ?, fullname = ? WHERE user_id = ? AND role_id = ?'
                        );
                        $stmt->bind_param('ssii', $form['username'], $fullName, $userId, $roleId);
                    } else {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare(
                            'UPDATE user SET username = ?, fullname = ?, password = ? WHERE user_id = ? AND role_id = ?'
                        );
                        $stmt->bind_param('sssii', $form['username'], $fullName, $passwordHash, $userId, $roleId);
                    }
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare(
                        'INSERT INTO user (username, password, fullname, join_date, role_id, leave_date)
                         VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, NULL)'
                    );
                    $stmt->bind_param('sssi', $form['username'], $passwordHash, $fullName, $roleId);
                    $stmt->execute();
                    $stmt->close();
                }

                $conn->commit();
                header('Location: /two-step/admin/account_management.php?type=' . rawurlencode($type) . '&saved=1');
                exit;
            } catch (RuntimeException $ex) {
                if ($transactionStarted) {
                    $conn->rollback();
                }
                $message = $ex->getMessage();
            } catch (mysqli_sql_exception $ex) {
                if ($transactionStarted) {
                    $conn->rollback();
                }
                $message = 'Could not save this account. Please check the details and try again.';
            }
        }
    } elseif ($action === 'toggle_active') {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        if ($userId === false || $userId < 1) {
            $message = 'Invalid account ID.';
        } else {
            $stmt = $conn->prepare('SELECT leave_date FROM user WHERE user_id = ? AND role_id = ?');
            $stmt->bind_param('ii', $userId, $roleId);
            $stmt->execute();
            $account = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$account) {
                $message = 'Account not found.';
            } else {
                $sql = $account['leave_date'] === null
                    ? 'UPDATE user SET leave_date = CURRENT_TIMESTAMP WHERE user_id = ? AND role_id = ?'
                    : 'UPDATE user SET leave_date = NULL WHERE user_id = ? AND role_id = ?';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $userId, $roleId);
                $stmt->execute();
                $stmt->close();

                header(
                    'Location: /two-step/admin/account_management.php?type=' . rawurlencode($type)
                    . '&edit=' . $userId . '&updated=1'
                );
                exit;
            }
        }
    } else {
        http_response_code(400);
        exit('Invalid account action.');
    }
}

if (($_GET['saved'] ?? '') === '1') {
    $message = $accountLabel . ' account saved successfully.';
} elseif (($_GET['updated'] ?? '') === '1') {
    $message = $accountLabel . ' account status updated.';
}

$stmt = $conn->prepare(
    'SELECT user_id, username, fullname, join_date, leave_date
     FROM user WHERE role_id = ? ORDER BY username'
);
$stmt->bind_param('i', $roleId);
$stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$isEditing = $form['user_id'] !== '';
if ($isEditing && $editingIsActive === null) {
    $editId = filter_var($form['user_id'], FILTER_VALIDATE_INT);
    if ($editId !== false && $editId > 0) {
        $stmt = $conn->prepare('SELECT leave_date FROM user WHERE user_id = ? AND role_id = ?');
        $stmt->bind_param('ii', $editId, $roleId);
        $stmt->execute();
        $accountStatus = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($accountStatus) {
            $editingIsActive = $accountStatus['leave_date'] === null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($accountLabel) ?> Account Management</title>
    <base href="/two-step/">
    <link rel="icon" type="image/png" href="images/logo.png">
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
        <h1><?= e($accountLabel) ?> Accounts</h1>

        <?php if ($message !== ''): ?>
            <div class="account-notice"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="staff-container">
            <h2><?= $isEditing ? 'Edit' : 'Add' ?> <?= e($accountLabel) ?> Account</h2>
            <form method="POST" class="account-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="type" value="<?= e($type) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="user_id" value="<?= e($form['user_id']) ?>">

                <label for="username">Username</label>
                <input id="username" name="username" type="text" maxlength="255" value="<?= e($form['username']) ?>" required>

                <label for="fullname">Full name</label>
                <input id="fullname" name="fullname" type="text" maxlength="255" value="<?= e($form['fullname']) ?>">

                <label for="password"><?= $isEditing ? 'New password (optional)' : 'Password' ?></label>
                <input id="password" name="password" type="password" minlength="6" maxlength="255" <?= $isEditing ? '' : 'required' ?>>

                <div class="account-form-actions">
                    <button class="account-btn" type="submit"><?= $isEditing ? 'Save Changes' : 'Add ' . e($accountLabel) ?></button>
                    <?php if ($isEditing): ?>
                        <a class="account-btn secondary" href="admin/account_management.php?type=<?= e($type) ?>">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($isEditing && $editingIsActive !== null): ?>
                <div class="account-status-control">
                    <p>
                        Account status:
                        <?= $editingIsActive ? 'Active' : 'Deactivated' ?>
                    </p>
                    <form method="POST" onsubmit="return confirm('<?= $editingIsActive ? 'Deactivate' : 'Activate' ?> this account?');">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="type" value="<?= e($type) ?>">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="user_id" value="<?= (int)$form['user_id'] ?>">
                        <button class="account-btn <?= $editingIsActive ? 'danger' : 'secondary' ?>" type="submit">
                            <?= $editingIsActive ? 'Deactivate Account' : 'Activate Account' ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </section>

        <section class="staff-container">
            <h2>Manage <?= e($accountLabel) ?> Accounts</h2>
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
                        <?php if ($accounts): ?>
                            <?php foreach ($accounts as $account): ?>
                                <?php $active = $account['leave_date'] === null; ?>
                                <tr>
                                    <td><?= (int)$account['user_id'] ?></td>
                                    <td><?= e($account['username']) ?></td>
                                    <td><?= e($account['fullname'] ?? '') ?></td>
                                    <td><?= $account['join_date'] ? e(date('d M Y', strtotime($account['join_date']))) : 'Not set' ?></td>
                                    <td>
                                        <?= $active ? 'Active' : 'Deactivated' ?>
                                    </td>
                                    <td>
                                        <a class="manage-btn" href="admin/account_management.php?type=<?= e($type) ?>&amp;edit=<?= (int)$account['user_id'] ?>">Manage</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="empty-accounts">No <?= strtolower(e($accountLabel)) ?> accounts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
