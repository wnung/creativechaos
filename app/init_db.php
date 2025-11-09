<?php
require_once __DIR__.'/inc/db.php';
$pdo=cc_db();
$sql=file_get_contents(__DIR__.'/sql/schema.sql');
$pdo->exec($sql);

// Create initial admin if none exists
$exists=$pdo->query("SELECT COUNT(*) c FROM admins")->fetch()['c'] ?? 0;
if(!$exists){
  $email = $_POST['email'] ?? 'admin@yourdomain.com';
  $pass  = $_POST['password'] ?? bin2hex(random_bytes(6));
  $hash  = password_hash($pass, PASSWORD_BCRYPT);
  $stmt=$pdo->prepare("INSERT INTO admins (email,password_hash,role,is_active) VALUES (?,?, 'super', 1)");
  $stmt->execute([$email,$hash]);
  echo "Database initialized. Admin created:<br>Email: ".htmlspecialchars($email)."<br>Password: <code>".htmlspecialchars($pass)."</code>";
} else {
  echo "Database already initialized.";
}
