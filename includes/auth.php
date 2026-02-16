<?php
require_once __DIR__ . '/../backend/config.php';
ensure_session();

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        redirect('/auth/login.php');
    }
}

function user_can(array $allowed_role_codes): bool
{
    if (empty($_SESSION['user'])) return false;
    $roleCode = $_SESSION['user']['role_code'] ?? '';
    return in_array($roleCode, $allowed_role_codes, true);
}

function hasRole(string $role): bool
{
    if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
        return false;
    }

    static $userRoles = [];
    $userId = $_SESSION['user']['id'];

    if (!isset($userRoles[$userId])) {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("
            SELECT r.name 
            FROM roles r 
            JOIN user_roles ur ON ur.role_id = r.id 
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        $userRoles[$userId] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return in_array($role, $userRoles[$userId]);
}

