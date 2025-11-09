<?php
require_once __DIR__.'/inc/functions.php';
cc_boot_session();
header('Content-Type: text/plain; charset=utf-8');
echo "Creative Chaos Diagnostics\n";
echo "PHP: ".PHP_VERSION."\n";
echo "Session save path: ".session_save_path()."\n";
echo "App URL: ".(cc_env()['app_url'] ?? '(unset)')."\n";
echo "Config loaded: OK\n";
echo "Time: ".date('c')."\n";
try {
  require_once __DIR__.'/inc/db.php';
  $pdo=cc_db();
  echo "DB connection: OK\n";
} catch (Throwable $e){
  echo "DB connection error: ".$e->getMessage()."\n";
}
