<?php
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
cc_require_login();
if (!cc_is_super()) {
    http_response_code(403);
    exit('Forbidden');
}
$pdo = cc_db();
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cc_csrf_check();
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] === 'super' ? 'super' : 'staff';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Invalid email.';
    } else {
        $pass = bin2hex(random_bytes(6));
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        try {
            $st = $pdo->prepare('INSERT INTO admins (email,password_hash,role,is_active) VALUES (?,?,?,1)');
            $st->execute([$email, $hash, $role]);
            $msg = 'Admin created. Temporary password: <code>' . htmlspecialchars($pass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>';
        } catch (Throwable $e) {
            $err = 'Could not create admin. Email may already exist.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>New Admin • Creative Chaos</title>
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/styles.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/css/cc-theme.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
</head>
<body class="container">
<h2>New Admin</h2>
<?php if ($msg): ?><div class="alert"><?=$msg?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?=htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?=cc_csrf_token()?>">
  <label class="required">Email</label><input type="email" name="email" required>
  <label class="required">Role</label>
  <select name="role">
    <option value="staff">Staff</option>
    <option value="super">Super</option>
  </select>
  <div class="flex">
    <button class="btn" type="submit">Create Admin</button>
    <a class="btn outline" href="users.php">Cancel</a>
  </div>
</form>
</body>
</html>
