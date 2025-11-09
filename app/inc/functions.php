<?php
function cc_env(): array {
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $defaults = [
        'db_dsn' => null,
        'db_host' => '127.0.0.1',
        'db_port' => null,
        'db_name' => 'creativechaos',
        'db_user' => 'root',
        'db_pass' => '',
        'db_socket' => null,
        'db_charset' => 'utf8mb4',
        'app_url' => '',
        'admin_emails' => [],
        'session_save_path' => null,
        'smtp' => [
            'enabled' => false,
            'host' => 'localhost',
            'port' => 587,
            'user' => '',
            'pass' => '',
            'from_email' => 'registration@example.com',
            'from_name' => 'Creative Chaos',
        ],
        'google_sheets' => [
            'enabled' => false,
            'service_account_json' => __DIR__ . '/../service_account.json',
            'sheet_id' => '',
            'range' => 'Registrations!A1',
            'value_input_option' => 'RAW',
        ],
    ];

    $files = [__DIR__ . '/../config.php'];
    $local = __DIR__ . '/../config.local.php';
    if (is_file($local)) {
        $files[] = $local;
    }

    $loaded = [];
    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }
        $candidate = include $file;
        if (is_array($candidate)) {
            $loaded = array_replace_recursive($loaded, $candidate);
        }
    }

    $cfg = array_replace_recursive($defaults, $loaded);

    if (is_string($cfg['admin_emails'])) {
        $parts = array_map('trim', explode(',', $cfg['admin_emails']));
        $cfg['admin_emails'] = array_values(array_filter($parts, static function ($v) {
            return $v !== '';
        }));
    }
    if (!is_array($cfg['admin_emails'])) {
        $cfg['admin_emails'] = [];
    }

    return $cfg;
}

function cc_boot_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $cfg = cc_env();
    if (!empty($cfg['session_save_path'])) {
        @session_save_path($cfg['session_save_path']);
    }
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('cc_portal');
    @session_start();
}

function cc_base_url(): string {
    $cfg = cc_env();
    if (!empty($cfg['app_url'])) {
        return rtrim($cfg['app_url'], '/');
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return '';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    return $scheme . '://' . $host;
}

function cc_url(string $path = ''): string {
    $base = cc_base_url();
    $trimmed = ltrim($path, '/');
    if ($path === '' || $path === '/') {
        return $base !== '' ? $base : '/';
    }
    if ($base !== '') {
        return $base . '/' . $trimmed;
    }
    return '/' . $trimmed;
}

function cc_asset(string $path): string {
    return cc_url($path);
}

function cc_csrf_token(): string {
    cc_boot_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function cc_csrf_check(): void {
    cc_boot_session();
    $sent = $_POST['csrf'] ?? '';
    $real = $_SESSION['csrf'] ?? '';
    if ($sent === '' || $real === '' || !hash_equals($real, $sent)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
}

function cc_flash(string $msg): void {
    cc_boot_session();
    $_SESSION['flash'] = $msg;
}

function cc_flash_render(): void {
    cc_boot_session();
    if (empty($_SESSION['flash'])) {
        return;
    }
    $msg = $_SESSION['flash'];
    $cls = (strpos($msg, '✅') === 0) ? 'alert success flash' : 'alert flash';
    echo '<div class="' . htmlspecialchars($cls, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
    unset($_SESSION['flash']);
}

function cc_sanitize_filename(string $name): string {
    return preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
}
