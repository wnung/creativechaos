<?php
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
cc_require_login();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin • Creative Chaos</title>
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/styles.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/css/cc-theme.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
</head>
<body>
<div class="container">
<h2>Creative Chaos • Admin</h2>
<p class="help">Overview and data tools for registrations, submissions, and the author showcase.</p>
<div class="admin-nav">
  <a class="badge" href="dashboard.php">Dashboard</a>
  <a class="badge" href="registrations.php">Registrations</a>
  <a class="badge" href="submissions.php">Submissions</a>
  <a class="badge" href="authors.php">Authors</a>
  <a class="badge" href="export.php">Export CSV</a>
  <?php if (cc_is_super()): ?><a class="badge" href="users.php">Users</a><a class="badge" href="remake_db.php">Remake DB</a><?php endif; ?>
  <a class="badge" href="change_password.php">Change Password</a>
  <a class="badge" href="logout.php">Logout</a>
</div>
<hr>
