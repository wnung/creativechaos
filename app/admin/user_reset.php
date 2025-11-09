<?php
require_once __DIR__.'/../inc/functions.php';
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/db.php';
cc_require_login();
if(!cc_is_super()){ http_response_code(403); exit('Forbidden'); }
$pdo=cc_db();
$id=(int)($_GET['id']??0);
if($id<=0){ exit('Invalid id'); }
$pass = bin2hex(random_bytes(6));
$hash = password_hash($pass, PASSWORD_BCRYPT);
$up=$pdo->prepare("UPDATE admins SET password_hash=? WHERE id=?"); $up->execute([$hash,$id]);
echo "Temporary password: <code>".htmlspecialchars($pass)."</code> <br><a href='users.php'>Back</a>";
