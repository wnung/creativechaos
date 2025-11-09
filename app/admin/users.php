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
$rows = $pdo->query('SELECT id,email,role,is_active,created_at FROM admins ORDER BY id ASC')->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Users • Creative Chaos</title>
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/styles.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<link rel="stylesheet" href="<?=htmlspecialchars(cc_asset('assets/css/cc-theme.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
</head>
<body class="container">
<h2>Admin Users</h2>
<p class="help">Create additional admins, toggle active status, reset passwords.</p>
<p><a class="btn" href="user_new.php">+ New Admin</a> <a class="btn outline" href="dashboard.php">Back to Admin</a></p>
<table class="table">
<thead><tr><th>ID</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?=$r['id']?></td>
  <td><?=htmlspecialchars($r['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></td>
  <td><?=htmlspecialchars(strtoupper($r['role']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></td>
  <td><?=$r['is_active'] ? 'Active' : 'Disabled'?></td>
  <td class="flex">
    <a class="btn outline" href="user_toggle.php?id=<?=$r['id']?>"><?=$r['is_active'] ? 'Disable' : 'Enable'?></a>
    <a class="btn outline" href="user_reset.php?id=<?=$r['id']?>">Reset Password</a>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
