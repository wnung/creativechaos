<?php
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
if (cc_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cc_csrf_check();
    if (cc_login($_POST['email'], $_POST['password'])) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid credentials';
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login • Creative Chaos</title>
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/styles.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/css/cc-theme.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
</head>
<body class="container">
<h2>Admin Login</h2>
<?php if ($error): ?><div class="alert"><?=htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?=cc_csrf_token()?>">
  <label>Email</label><input name="email" type="email" required>
  <label>Password</label><input name="password" type="password" required>
  <button class="btn" type="submit">Sign In</button>
</form>
<p class="small"><a href="<?=htmlspecialchars(cc_url(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">← Back to site</a></p>
</body>
</html>
