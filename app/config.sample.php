<?php
return [
    // DreamHost shared hosting default example values — update for your account.
    'db_host' => 'mysql.example.com',
    'db_port' => 3306,
    'db_name' => 'creativechaos',
    'db_user' => 'dbuser',
    'db_pass' => 'changeme',
    'db_charset' => 'utf8mb4',

    // Optional DSN override. Leave null to build from host/port/name above.
    'db_dsn'  => null,
    'db_socket' => null,

    // Public URL for the app. Include the subdirectory if uploaded under one.
    'app_url' => 'https://example.com/creative-chaos',

    // Notification recipients for new registrations.
    'admin_emails' => ['registrar@example.com'],

    // Uncomment to force PHP session files into a custom directory.
    //'session_save_path' => '/home/username/tmp',

    'smtp' => [
        'enabled'    => false,
        'host'       => 'sub5.mail.dreamhost.com',
        'port'       => 587,
        'user'       => 'user@example.com',
        'pass'       => 'changeme',
        'from_email' => 'registration@example.com',
        'from_name'  => 'Creative Chaos',
    ],

    'google_sheets' => [
        'enabled'            => false,
        'service_account_json' => __DIR__ . '/service_account.json',
        'sheet_id'           => '',
        'range'              => 'Registrations!A1',
        'value_input_option' => 'RAW',
    ],
];
