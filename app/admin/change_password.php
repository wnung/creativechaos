<?php
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
cc_require_login();
$pdo = cc_db();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cc_csrf_check();
    $curr = $_POST['current'] ?? '';
    $new = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (!$new || strlen($new) < 8) {
        $err = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $err = 'New password and confirmation do not match.';
    } else {
        $st = $pdo->prepare('SELECT password_hash FROM admins WHERE id=?');
        $st->execute([$_SESSION['admin_id']]);
        $row = $st->fetch();
        if (!$row || !password_verify($curr, $row['password_hash'])) {
            $err = 'Current password is incorrect.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $up = $pdo->prepare('UPDATE admins SET password_hash=? WHERE id=?');
            $up->execute([$hash, $_SESSION['admin_id']]);
            $msg = 'Password updated successfully.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Change Password • Creative Chaos</title>
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/styles.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/css/cc-theme.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
</head>
<body class="container">
<h2>Change Password</h2>
<?php if ($msg): ?><div class="alert"><?=htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?=htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?=cc_csrf_token()?>">
  <label>Current Password</label>
  <input type="password" name="current" required>
  <label>New Password</label>
  <input type="password" name="new" required>
  <label>Confirm New Password</label>
  <input type="password" name="confirm" required>
  <div class="flex">
    <button class="btn" type="submit">Update Password</button>
    <a class="btn outline" href="dashboard.php">Cancel</a>
  </div>
</form>
</body>
</html>
