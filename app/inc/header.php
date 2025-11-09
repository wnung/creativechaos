<?php
require_once __DIR__ . '/functions.php';
cc_boot_session();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Creative Chaos • Writing Competition</title>
<meta name="description" content="Register teams or open class writers, upload entries, and manage the Creative Chaos writing competition.">
<meta name="theme-color" content="#0c1222">
<link rel="icon" href="<?=htmlspecialchars(cc_asset('assets/logo.svg'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>" type="image/svg+xml">
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/styles.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/css/cc-theme.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<script defer src="<?=htmlspecialchars(cc_asset('assets/script.js'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>"></script>
</head>
<body>
<div class="header-wrap">
  <nav class="nav container" aria-label="Main">
    <div class="brand">
      <img src="<?=htmlspecialchars(cc_asset('assets/logo.svg'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>" alt="" aria-hidden="true">
      <h1>Creative Chaos • Writing Competition</h1>
    </div>
    <div class="menu" role="navigation">
      <a href="<?=htmlspecialchars(cc_url(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">Home</a>
      <a href="<?=htmlspecialchars(cc_url('register.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">Registration</a>
      <a href="<?=htmlspecialchars(cc_url('submit.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">Submit Entry</a>
      <a href="<?=htmlspecialchars(cc_url('author_signup.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">Author Showcase</a>
      <a href="<?=htmlspecialchars(cc_url('admin/login.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>" class="badge">Admin</a>
    </div>
  </nav>
</div>
<div class="container">
<?php cc_flash_render(); ?>
