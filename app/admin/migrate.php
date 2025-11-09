<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/auth.php';
cc_require_login();
if (!cc_is_super()) { http_response_code(403); exit('Forbidden'); }
$pdo=cc_db();
function col_exists($pdo,$table,$col){
  $st=$pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?"); $st->execute([$col]); return (bool)$st->fetch();
}
$changed=[];
if(!col_exists($pdo,'admins','role')){
  $pdo->exec("ALTER TABLE admins ADD COLUMN role ENUM('super','staff') NOT NULL DEFAULT 'staff' AFTER password_hash");
  $changed[]="Added admins.role";
}
if(!col_exists($pdo,'admins','is_active')){
  $pdo->exec("ALTER TABLE admins ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
  $changed[]="Added admins.is_active";
}
echo $changed ? ("Migration complete:<br>- ".implode("<br>- ",$changed)) : "No changes needed.";
