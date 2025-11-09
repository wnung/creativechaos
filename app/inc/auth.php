<?php
require_once __DIR__ . '/functions.php';
cc_boot_session();
require_once __DIR__ . '/db.php';

function cc_is_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function cc_require_login(): void {
    if (!cc_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function cc_admin(): array {
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null,
        'role' => $_SESSION['admin_role'] ?? 'staff',
    ];
}

function cc_is_super(): bool {
    return (cc_admin()['role'] ?? 'staff') === 'super';
}

function cc_login(string $email, string $password): bool {
    $pdo = cc_db();
    $st = $pdo->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $r = $st->fetch();
    if ($r && (int) $r['is_active'] === 1 && password_verify($password, $r['password_hash'])) {
        $_SESSION['admin_id'] = $r['id'];
        $_SESSION['admin_email'] = $r['email'];
        $_SESSION['admin_role'] = $r['role'] ?? 'staff';
        return true;
    }
    return false;
}

function cc_logout(): void {
    cc_boot_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
    }
    session_destroy();
}
