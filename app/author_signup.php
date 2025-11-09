<?php
require_once __DIR__.'/inc/header.php';
require_once __DIR__.'/inc/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cc_csrf_check();
  $pdo=cc_db();
  $stmt=$pdo->prepare("INSERT INTO authors (name, email, phone, genre, website, notes, created_at) VALUES (?,?,?,?,?,?,NOW())");
  $stmt->execute([$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['genre'],$_POST['website'],$_POST['notes']]);
  cc_flash('Thanks! We will follow up with details about the showcase.');
  header('Location: author_signup.php'); exit;
}
?>
<h2>Author Showcase Application</h2>
<p class="help">There is no fee to participate. Authors may bring books to sell. Space is limited.</p>
<form method="post">
  <input type="hidden" name="csrf" value="<?=cc_csrf_token()?>">
  <label class="required">Name</label><input name="name" required>
  <label class="required">Email</label><input type="email" name="email" required>
  <label>Phone</label><input name="phone">
  <label>Genre / Focus</label><input name="genre" placeholder="YA, Sci-Fi, Poetry, Non-fiction, etc.">
  <label>Website / Social</label><input name="website" placeholder="https://…">
  <label>Notes</label><textarea name="notes" rows="4"></textarea>
  <button class="btn" type="submit">Apply</button>
</form>
<?php require __DIR__.'/inc/footer.php'; ?>
