<?php
require_once __DIR__ . '/functions.php';

function cc_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = cc_env();
    $dsn = $cfg['db_dsn'] ?? null;
    $user = $cfg['db_user'] ?? null;
    $pass = $cfg['db_pass'] ?? null;

    if (!$dsn) {
        $host = $cfg['db_host'] ?? '127.0.0.1';
        $name = $cfg['db_name'] ?? '';
        $charset = $cfg['db_charset'] ?? 'utf8mb4';
        $dsnParts = ['mysql:host=' . $host, 'dbname=' . $name, 'charset=' . $charset];
        if (!empty($cfg['db_port'])) {
            $dsnParts[] = 'port=' . (int) $cfg['db_port'];
        }
        if (!empty($cfg['db_socket'])) {
            $dsnParts[] = 'unix_socket=' . $cfg['db_socket'];
        }
        $dsn = implode(';', $dsnParts);
    }

    if ($user === '') {
        $user = null;
    }
    if ($pass === '') {
        $pass = null;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    if (strpos($dsn, 'sqlite:') === 0) {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}
