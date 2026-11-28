<?php
function require_role(int $requiredRoleId): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
        header("Location: /two-step/auth/login/login.html");
        exit;
    }

    if ((int)$_SESSION['role_id'] !== $requiredRoleId) {
        http_response_code(403);
        exit('Forbidden');
    }
}
