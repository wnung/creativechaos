<?php
$bool = static function ($value, $default = false) {
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }
    $normalized = strtolower((string) $value);
    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }
    return $default;
};

$list = static function ($value) {
    if (is_array($value)) {
        $values = array_map('trim', $value);
    } elseif ($value === null || $value === '') {
        $values = [];
    } else {
        $values = array_map('trim', explode(',', (string) $value));
    }

    return array_values(array_filter($values, static function ($v) {
        return $v !== '';
    }));
};

$portEnv = getenv('CC_DB_PORT');
return [
    // Database configuration. Provide either CC_DB_DSN or individual host/name credentials.
    'db_dsn'    => getenv('CC_DB_DSN') ?: null,
    'db_host'   => getenv('CC_DB_HOST') ?: '127.0.0.1',
    'db_port'   => ($portEnv !== false && $portEnv !== '' ? (int) $portEnv : null),
    'db_name'   => getenv('CC_DB_NAME') ?: 'creativechaos',
    'db_user'   => getenv('CC_DB_USER') ?: 'root',
    'db_pass'   => getenv('CC_DB_PASS') ?: '',
    'db_socket' => getenv('CC_DB_SOCKET') ?: null,
    'db_charset'=> getenv('CC_DB_CHARSET') ?: 'utf8mb4',

    // Optional absolute base URL. Leave blank to auto-detect from the current request.
    'app_url' => rtrim(getenv('CC_APP_URL') ?: '', '/'),

    // Admin notification emails (comma-separated list via CC_ADMIN_EMAILS).
    'admin_emails' => $list(getenv('CC_ADMIN_EMAILS')),

    // Optional custom session save path (CC_SESSION_SAVE_PATH).
    'session_save_path' => getenv('CC_SESSION_SAVE_PATH') ?: null,

    // Email delivery configuration.
    'smtp' => [
        'enabled'    => $bool(getenv('CC_SMTP_ENABLED'), false),
        'host'       => getenv('CC_SMTP_HOST') ?: 'localhost',
        'port'       => (int) (getenv('CC_SMTP_PORT') ?: 587),
        'user'       => getenv('CC_SMTP_USER') ?: '',
        'pass'       => getenv('CC_SMTP_PASS') ?: '',
        'from_email' => getenv('CC_SMTP_FROM_EMAIL') ?: 'registration@example.com',
        'from_name'  => getenv('CC_SMTP_FROM_NAME') ?: 'Creative Chaos',
    ],

    // Google Sheets integration.
    'google_sheets' => [
        'enabled'             => $bool(getenv('CC_GOOGLE_SHEETS_ENABLED'), false),
        'service_account_json'=> getenv('CC_GOOGLE_SHEETS_KEY') ?: __DIR__ . '/service_account.json',
        'sheet_id'            => getenv('CC_GOOGLE_SHEETS_ID') ?: '',
        'range'               => getenv('CC_GOOGLE_SHEETS_RANGE') ?: 'Registrations!A1',
        'value_input_option'  => getenv('CC_GOOGLE_SHEETS_VALUE_INPUT') ?: 'RAW',
    ],
];
