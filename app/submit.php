<?php
require_once __DIR__.'/inc/header.php';
require_once __DIR__.'/inc/db.php';

$maxSize = 10*1024*1024; // 10 MB
$allowed = ['pdf','doc','docx'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cc_csrf_check();
  $pdo = cc_db();

  // Lookup: guardian + (student OR team)
  $student = trim($_POST['student_name'] ?? '');
  $team = trim($_POST['team_name'] ?? '');
  $guardian = trim($_POST['guardian_email'] ?? '');

  $row = null;
  if ($team) {
    $st = $pdo->prepare("SELECT id FROM registrations WHERE guardian_email=? AND team_name=? AND registration_type='team' ORDER BY id DESC LIMIT 1");
    $st->execute([$guardian, $team]);
    $row = $st->fetch();
  } else {
    $st = $pdo->prepare("SELECT id FROM registrations WHERE guardian_email=? AND student_name=? AND registration_type='open' ORDER BY id DESC LIMIT 1");
    $st->execute([$guardian, $student]);
    $row = $st->fetch();
  }
  if(!$row){ cc_flash('No matching registration found. Please register first.'); header('Location: submit.php'); exit; }

  if(!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK){
    cc_flash('Please choose a file to upload.'); header('Location: submit.php'); exit;
  }
  $ext=strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
  if(!in_array($ext,$allowed)){ cc_flash('Only PDF/DOC/DOCX allowed.'); header('Location: submit.php'); exit; }
  if($_FILES['file']['size']>$maxSize){ cc_flash('File too large (max 10 MB).'); header('Location: submit.php'); exit; }

  $safe=cc_sanitize_filename($_FILES['file']['name']);
  $destDir = __DIR__ . '/uploads';
  if(!is_dir($destDir)) mkdir($destDir,0775,true);
  $final = $destDir . '/' . time() . '_' . $safe;
  move_uploaded_file($_FILES['file']['tmp_name'], $final);

  $stmt=$pdo->prepare("INSERT INTO submissions (registration_id, filename, original_name, notes, created_at) VALUES (?,?,?,?,NOW())");
  $stmt->execute([$row['id'], basename($final), $_FILES['file']['name'], $_POST['notes'] ?? '']);

  cc_flash('Submission uploaded! You may resubmit to replace your entry before the deadline.');
  header('Location: submit.php'); exit;
}
?>
<h2>Submit Your Entry</h2>
<p class="help">Use the same guardian email and either the team name (team registration) or student name (open class) from registration.</p>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?=cc_csrf_token()?>">
  <div class="row">
    <div>
      <label>Team Name (Team registrations)</label>
      <input name="team_name" placeholder="Team name if applicable">
    </div>
    <div>
      <label>Student Name (Open Class)</label>
      <input name="student_name" placeholder="Student name if applicable">
    </div>
  </div>
  <label class="required">Guardian Email</label>
  <input type="email" name="guardian_email" required>
  <label class="required">Upload File (PDF/DOC/DOCX, max 10 MB)</label>
  <input type="file" name="file" accept=".pdf,.doc,.docx" required>
  <label>Notes to judges (optional)</label>
  <textarea name="notes" rows="4" placeholder="Optional context, content warnings, etc."></textarea>
  <button class="btn" type="submit">Upload Entry</button>
</form>
<?php require __DIR__.'/inc/footer.php'; ?>
