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
    $confirm = trim($_POST['confirm'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newpass = trim($_POST['password'] ?? '');
    if ($confirm !== 'REMAKE') {
        $err = 'Type REMAKE in the box to confirm.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please provide a valid admin email to seed.';
    } elseif (strlen($newpass) < 8) {
        $err = 'New password must be at least 8 characters.';
    } else {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach (['submissions','registration_writers','registrations','authors','admins'] as $t) {
                try {
                    $pdo->exec("DROP TABLE IF EXISTS `$t`");
                } catch (Throwable $e) {
                    // continue dropping remaining tables
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
            $pdo->exec($schema);

            $hash = password_hash($newpass, PASSWORD_BCRYPT);
            $st = $pdo->prepare('INSERT INTO admins (email,password_hash,role,is_active) VALUES (?,?,\'super\',1)');
            $st->execute([$email, $hash]);

            $msg = 'Database remade successfully. Super admin seeded for ' . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '.';
        } catch (Throwable $e) {
            $err = 'Remake failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Remake Database • Creative Chaos</title>
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/styles.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/css/cc-theme.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
</head>
<body class="container">
<h2>Remake Database</h2>
<div class="alert">
  <strong>Danger:</strong> This will <u>DROP and RECREATE</u> all tables:
  <code>admins, registrations, registration_writers, submissions, authors</code>.
  All data will be permanently deleted. This action is only available to SUPER admins.
</div>

<?php if ($msg): ?><div class="alert"><?=$msg?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?=$err?></div><?php endif; ?>

<form method="post">
  <input type="hidden" name="csrf" value="<?=cc_csrf_token()?>">
  <label class="required">Type <code>REMAKE</code> to confirm</label>
  <input name="confirm" placeholder="REMAKE" required>
  <div class="row">
    <div>
      <label class="required">Seed Admin Email</label>
      <input type="email" name="email" placeholder="you@yourdomain.com" required>
    </div>
    <div>
      <label class="required">Seed Admin Password</label>
      <input type="password" name="password" placeholder="Min 8 characters" required>
    </div>
  </div>
  <div class="flex">
    <button class="btn" type="submit">I understand — Remake Database</button>
    <a class="btn outline" href="dashboard.php">Cancel</a>
  </div>
</form>

<p class="small">Tip: export CSVs first from <a href="export.php">Export CSV</a> if you want a backup.</p>
</body>
</html>
