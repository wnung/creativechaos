<?php
require_once __DIR__.'/../inc/functions.php';
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/db.php';
cc_require_login();
$pdo=cc_db();
$id=(int)($_GET['id']??0);
$stmt=$pdo->prepare("SELECT filename, original_name FROM submissions WHERE id=?");
$stmt->execute([$id]);
$row=$stmt->fetch();
if(!$row) exit('Not found');
$path=__DIR__ . '/../uploads/' . $row['filename'];
if(!is_file($path)) exit('Missing file');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($row['original_name']).'"');
readfile($path);
