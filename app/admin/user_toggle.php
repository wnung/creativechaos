<?php
require_once __DIR__.'/../inc/functions.php';
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/db.php';
cc_require_login();
if(!cc_is_super()){ http_response_code(403); exit('Forbidden'); }
$pdo=cc_db();
$id=(int)($_GET['id']??0);
if($id<=0){ exit('Invalid id'); }
if($id===$_SESSION['admin_id']){ exit('You cannot disable yourself.'); }
$st=$pdo->prepare("SELECT is_active FROM admins WHERE id=?"); $st->execute([$id]); $r=$st->fetch();
if(!$r){ exit('Not found'); }
$new = $r['is_active']?0:1;
$up=$pdo->prepare("UPDATE admins SET is_active=? WHERE id=?"); $up->execute([$new,$id]);
header('Location: users.php'); exit;
