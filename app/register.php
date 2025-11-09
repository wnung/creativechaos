<?php
declare(strict_types=1);
require_once __DIR__.'/inc/functions.php';

/**
 * Creative Chaos — Schema-aware Registration
 * - Uses db.php (cc_db()) for PDO.
 * - Writes to `registrations` and `registration_writers` per provided schema.
 * - 7 writers included for team; +$10 each beyond 7. Open = $10/person.
 * - POST handled before any output; redirects to Givebutter on success.
 * - Falls back to JSONL and writes diagnostics if DB is unavailable.
 */

// ---------- Config ----------
$GIVEBUTTER_URL = 'https://givebutter.com/TVChaosWriting';
$TEAM_BASE_INCLUDED = 7;
$TEAM_BASE_PRICE    = 100;
$TEAM_EXTRA_PRICE   = 10;
$OPEN_PRICE_PER     = 10;

$DATA_DIR   = __DIR__ . '/data';
$LOG_FILE   = $DATA_DIR . '/submit.log';
$JSONL_FILE = $DATA_DIR . '/submissions.jsonl';

// ---------- Utils ----------
function cc_now(): string { return (new DateTime('now', new DateTimeZone('UTC')))->format('c'); }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function cc_log(string $msg): void {
  global $LOG_FILE;
  if (!is_dir(dirname($LOG_FILE))) @mkdir(dirname($LOG_FILE), 0775, true);
  @file_put_contents($LOG_FILE, '['.cc_now()."] ".$msg.PHP_EOL, FILE_APPEND | LOCK_EX);
}
function cc_jsonl(array $record): void {
  global $JSONL_FILE;
  if (!is_dir(dirname($JSONL_FILE))) @mkdir(dirname($JSONL_FILE), 0775, true);
  @file_put_contents($JSONL_FILE, json_encode($record, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
}
function cc_pdo(): ?PDO {
  $cands = [__DIR__ . '/db.php', __DIR__ . '/inc/db.php', __DIR__ . '/includes/db.php', __DIR__ . '/_inc/db.php'];
  foreach ($cands as $p) {
    if (is_file($p)) {
      try { require_once $p; } catch (Throwable $e) { cc_log("db include error: ".$e->getMessage()); }
      if (function_exists('cc_db')) {
        try { $x = cc_db(); if ($x instanceof PDO) return $x; } catch (Throwable $e) { cc_log("cc_db() failed: ".$e->getMessage()); }
      }
      if (isset($pdo) && $pdo instanceof PDO) return $pdo;
    }
  }
  return null;
}
function cc_redirect(string $url): void {
  if (!headers_sent()) { header('Location: '.$url, true, 303); exit; }
  echo '<!doctype html><meta http-equiv="refresh" content="0;url='.h($url).'">';
  echo '<script>location.replace('.json_encode($url).');</script>';
  echo '<p>Redirecting to <a href="'.h($url).'">'.h($url).'</a>…</p>';
  exit;
}

// ---------- State ----------
$want = (isset($_GET['type']) && in_array($_GET['type'], ['team','open'], true)) ? $_GET['type'] : 'team';
$errors = [];
$team_writer_count = $TEAM_BASE_INCLUDED;
$team_fee = $TEAM_BASE_PRICE;
$open_fee = 0;

// ---------- Handle POST first ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $want = (isset($_POST['type']) && in_array($_POST['type'], ['team','open'], true)) ? $_POST['type'] : $want;
  try {
    $pdo = cc_pdo();
    if ($want === 'team') {
      $team_name = trim($_POST['team_name'] ?? '');
      $school    = trim($_POST['school'] ?? '');
      $guardian_email = trim($_POST['guardian_email'] ?? '');
      $grade     = trim($_POST['grade'] ?? '6-8 Team');   // schema requires NOT NULL
      $category  = trim($_POST['category'] ?? 'Team');    // schema requires NOT NULL
      $writer_count = max(0, (int)($_POST['writer_count'] ?? 0));
      $writers = array_map('trim', $_POST['writers'] ?? []);
      $writers = array_values(array_filter($writers, static function ($x) {
        return $x !== '';
      }));

      if ($team_name === '') $errors[] = 'Team name is required.';
      if ($school === '') $errors[] = 'School is required.';
      if (!filter_var($guardian_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid guardian email is required.';
      if ($writer_count < 1) $errors[] = 'At least 1 writer is required.';
      if (count($writers) !== $writer_count) $errors[] = 'Writer list must match the writer count.';

      $extra = max(0, $writer_count - $TEAM_BASE_INCLUDED);
      $fee = $TEAM_BASE_PRICE + ($extra * $TEAM_EXTRA_PRICE);
      if (!$errors) {
        $payload = [
          'registration_type'=>'team','team_name'=>$team_name,'school'=>$school,'guardian_email'=>$guardian_email,
          'grade'=>$grade,'category'=>$category,'writer_count'=>$writer_count,'extra_writers'=>$extra,'fee'=>$fee,
          'writers'=>$writers,'ip'=>$_SERVER['REMOTE_ADDR'] ?? null,'received_at'=>cc_now()
        ];
        $ok = false;
        if ($pdo instanceof PDO) {
          try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("INSERT INTO registrations
              (registration_type, team_name, student_name, grade, school, guardian_email, category, writer_count, extra_writers, fee)
              VALUES ('team', :team_name, NULL, :grade, :school, :guardian_email, :category, :writer_count, :extra_writers, :fee)");
            $st->execute([
              ':team_name'=>$team_name, ':grade'=>$grade, ':school'=>$school, ':guardian_email'=>$guardian_email,
              ':category'=>$category, ':writer_count'=>$writer_count, ':extra_writers'=>$extra, ':fee'=>$fee
            ]);
            $reg_id = (int)$pdo->lastInsertId();
            if ($writer_count > 0) {
              $stw = $pdo->prepare("INSERT INTO registration_writers (registration_id, writer_name) VALUES (:rid, :name)");
              foreach ($writers as $w) { $stw->execute([':rid'=>$reg_id, ':name'=>$w]); }
            }
            $pdo->commit();
            $ok = true;
          } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            cc_log('DB insert (team) failed: '.$e->getMessage());
            $ok = false;
          }
        }
        if (!$ok) cc_jsonl($payload);
        cc_redirect($GIVEBUTTER_URL);
      }
      $team_writer_count = max(1, min(20, $writer_count));
      $team_fee = $fee;
    } else {
      // OPEN registration: multiple individual registrants under one registration row
      $school    = trim($_POST['school'] ?? '');
      $guardian_email = trim($_POST['guardian_email'] ?? '');
      $grade     = trim($_POST['grade'] ?? '');      // required by schema
      $category  = trim($_POST['category'] ?? '');   // required by schema

      $names  = $_POST['open_names']  ?? [];
      $emails = $_POST['open_emails'] ?? [];
      $phones = $_POST['open_phones'] ?? [];
      $rows = [];
      $maxi = max(count($names), count($emails), count($phones));
      for ($i=0; $i<$maxi; $i++){
        $n = trim($names[$i]  ?? ''); $e = trim($emails[$i] ?? ''); $p = trim($phones[$i] ?? '');
        if ($n==='' && $e==='' && $p==='') continue;
        if (!filter_var($e, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email for row '.($i+1).'.';
        $rows[] = ['name'=>$n,'email'=>$e,'phone'=>$p];
      }
      if ($school==='') $errors[] = 'School is required.';
      if (!filter_var($guardian_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid guardian email is required.';
      if ($grade==='') $errors[] = 'Grade is required.';
      if ($category==='') $errors[] = 'Category is required.';
      if (count($rows) < 1) $errors[] = 'Add at least one registrant.';

      $fee = count($rows) * $OPEN_PRICE_PER;
      if (!$errors) {
        $payload = [
          'registration_type'=>'open','school'=>$school,'guardian_email'=>$guardian_email,'grade'=>$grade,'category'=>$category,
          'registrants'=>$rows,'fee'=>$fee,'ip'=>$_SERVER['REMOTE_ADDR'] ?? null,'received_at'=>cc_now()
        ];
        $ok = false;
        if ($pdo instanceof PDO) {
          try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("INSERT INTO registrations
              (registration_type, team_name, student_name, grade, school, guardian_email, category, writer_count, extra_writers, fee)
              VALUES ('open', NULL, NULL, :grade, :school, :guardian_email, :category, :writer_count, 0, :fee)");
            $st->execute([
              ':grade'=>$grade, ':school'=>$school, ':guardian_email'=>$guardian_email,
              ':category'=>$category, ':writer_count'=>count($rows), ':fee'=>$fee
            ]);
            $reg_id = (int)$pdo->lastInsertId();
            if ($rows) {
              $stw = $pdo->prepare("INSERT INTO registration_writers (registration_id, writer_name, writer_email, writer_phone)
                                    VALUES (:rid, :name, :email, :phone)");
              foreach ($rows as $r) {
                $stw->execute([':rid'=>$reg_id, ':name'=>$r['name'], ':email'=>$r['email'], ':phone'=>$r['phone']]);
              }
            }
            $pdo->commit();
            $ok = true;
          } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            cc_log('DB insert (open) failed: '.$e->getMessage());
            $ok = false;
          }
        }
        if (!$ok) cc_jsonl($payload);
        cc_redirect($GIVEBUTTER_URL);
      }
      $open_fee = $fee;
    }
  } catch (Throwable $e) {
    cc_log('POST fatal: '.$e->getMessage());
    $errors[] = 'Unexpected error while saving your registration.';
  }
}

// ---------- Precompute for GET or POST-with-errors ----------
if ($want === 'team') {
  if (isset($_POST['writer_count'])) $team_writer_count = max(1, min(20, (int)$_POST['writer_count']));
  $extra = max(0, $team_writer_count - $TEAM_BASE_INCLUDED);
  $team_fee = $TEAM_BASE_PRICE + ($extra * $TEAM_EXTRA_PRICE);
} else {
  $names  = $_POST['open_names']  ?? [];
  $emails = $_POST['open_emails'] ?? [];
  $phones = $_POST['open_phones'] ?? [];
  $rows_count = 0;
  $maxi = max(count($names), count($emails), count($phones), 1);
  for ($i=0; $i<$maxi; $i++) {
    $n = trim($names[$i]  ?? ''); $e = trim($emails[$i] ?? ''); $p = trim($phones[$i] ?? '');
    if ($n==='' && $e==='' && $p==='') continue; $rows_count++;
  }
  $open_fee = $rows_count * $OPEN_PRICE_PER;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registration — Creative Chaos</title>
    <link rel="stylesheet" href="<?php echo h(cc_asset('assets/styles.css')); ?>">
  <link rel="stylesheet" href="<?php echo h(cc_asset('assets/css/cc-theme.css')); ?>">
  <style>
    .cc-wrap{max-width:960px;margin:0 auto;padding:24px 16px;}
    .cc-grid{display:grid;gap:12px;grid-template-columns:repeat(12,1fr);}
    .cc-6{grid-column:span 6}.cc-3{grid-column:span 3}.cc-12{grid-column:span 12}
    .cc-list{display:grid;gap:10px}
    .cc-rowline{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:center}
    @media (max-width:720px){.cc-6,.cc-3{grid-column:span 12}.cc-rowline{grid-template-columns:1fr}}
    label{display:block;margin-bottom:4px;font-weight:600}
  </style>
</head>
<body>
<?php foreach ([__DIR__.'/inc/header.php', __DIR__.'/includes/header.php', __DIR__.'/_inc/header.php', __DIR__.'/partials/header.php', __DIR__.'/header.php'] as $hp) { if (file_exists($hp)) { include_once $hp; break; } } ?>
<main class="container cc-wrap">
  <div class="card">
    <div class="tabs">
      <a class="tab <?php echo $want==='team'?'active':''; ?>" href="<?php echo h(cc_url('register.php')); ?>?type=team">Team Registration</a>
      <a class="tab <?php echo $want==='open'?'active':''; ?>" href="<?php echo h(cc_url('register.php')); ?>?type=open">Open Registration</a>
    </div>

    <?php foreach ($errors as $e): ?>
      <div class="notice-error alert-error"><?php echo h($e); ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?php echo h(cc_url('register.php')); ?>?type=<?php echo h($want); ?>" novalidate>
      <input type="hidden" name="type" value="<?php echo h($want); ?>">
      <div class="cc-grid">
        <div class="cc-6">
          <label>School</label>
          <input class="form-control" type="text" name="school" value="<?php echo h($_POST['school'] ?? ''); ?>" required>
        </div>
        <div class="cc-6">
          <label>Guardian Email</label>
          <input class="form-control" type="email" name="guardian_email" value="<?php echo h($_POST['guardian_email'] ?? ''); ?>" required>
        </div>
        <div class="cc-3">
          <label>Grade</label>
          <input class="form-control" type="text" name="grade" value="<?php echo h($_POST['grade'] ?? ''); ?>" placeholder="e.g., 6, 7, 8, or 6-8" required>
        </div>
        <div class="cc-3">
          <label>Category</label>
          <select class="form-control" name="category" required>
            <?php
              $cats = ['Narrative','Poem','Argumentative','Informational','Team'];
              $sel = $_POST['category'] ?? '';
              foreach ($cats as $c) {
                $s = ($sel===$c)?' selected':'';
                echo '<option value="'.h($c).'"'.$s.'>'.h($c).'</option>';
              }
            ?>
          </select>
        </div>
      </div>

      <?php if ($want === 'team'): ?>
        <div class="cc-grid" style="margin-top:12px">
          <div class="cc-6">
            <label>Team Name</label>
            <input class="form-control" type="text" name="team_name" value="<?php echo h($_POST['team_name'] ?? ''); ?>" required>
          </div>
          <div class="cc-3">
            <label>Number of Writers</label>
            <input class="form-control" id="writer_count" type="number" name="writer_count" min="1" max="20" value="<?php echo h($team_writer_count); ?>" required>
          </div>
          <div class="cc-3" style="display:flex;align-items:flex-end;">
            <span class="pill">Base $<?php echo $TEAM_BASE_PRICE; ?> (includes up to <?php echo $TEAM_BASE_INCLUDED; ?>)</span>
          </div>
          <div class="cc-12">
            <label>Writer Names</label>
            <div id="writer-list" class="cc-list">
              <?php $existing = $_POST['writers'] ?? [];
                for ($i=0; $i<$team_writer_count; $i++) {
                  $val = h($existing[$i] ?? '');
                  echo '<input class="form-control" type="text" name="writers[]" placeholder="Writer '.($i+1).'" value="'.$val.'">';
                }
              ?>
            </div>
            <p><strong>Total:</strong> <output id="team-total-fee"><?php echo '$'.number_format($team_fee, 2); ?></output>
            <small>&nbsp;(+ $<?php echo $TEAM_EXTRA_PRICE; ?> each over <?php echo $TEAM_BASE_INCLUDED; ?>)</small></p>
          </div>
        </div>
      <?php else: ?>
        <div style="margin-top:12px">
          <label>Registrants</label>
          <div id="open-writer-list" class="cc-list">
            <?php
              $names  = $_POST['open_names']  ?? [];
              $emails = $_POST['open_emails'] ?? [];
              $phones = $_POST['open_phones'] ?? [];
              $maxi = max(count($names), count($emails), count($phones), 1);
              for ($i=0; $i<$maxi; $i++) {
                $n = h($names[$i]  ?? ''); $e = h($emails[$i] ?? ''); $p = h($phones[$i] ?? '');
                echo '<div class="cc-rowline">';
                echo '<input class="form-control" type="text"  name="open_names[]"  placeholder="Name"  value="'.$n.'">';
                echo '<input class="form-control" type="email" name="open_emails[]" placeholder="Email" value="'.$e.'">';
                echo '<input class="form-control" type="text"  name="open_phones[]" placeholder="Phone" value="'.$p.'">';
                echo '<button type="button" class="btn outline" aria-label="Remove" onclick="this.parentElement.remove(); updateOpenFee();">Remove</button>';
                echo '</div>';
              }
            ?>
          </div>
          <p><strong>Total:</strong> <output id="open-total-fee"><?php echo '$'.number_format($open_fee, 2); ?></output></p>
          <button id="add-open-writer" type="button" class="btn outline">+ Add Registrant</button>
        </div>
      <?php endif; ?>

      <div style="margin-top:16px;"><button class="btn" type="submit">Submit Registration</button></div>
    </form>
  </div>
</main>
<?php foreach ([__DIR__.'/inc/footer.php', __DIR__.'/includes/footer.php', __DIR__.'/_inc/footer.php', __DIR__.'/partials/footer.php', __DIR__.'/footer.php'] as $fp) { if (file_exists($fp)) { include_once $fp; break; } } ?>
<script>
(function(){
  const money = (n)=>'$'+n.toFixed(2);
  const BASE_INCLUDED = <?php echo (int)$TEAM_BASE_INCLUDED; ?>;
  const BASE_PRICE = <?php echo (int)$TEAM_BASE_PRICE; ?>;
  const EXTRA_PRICE = <?php echo (int)$TEAM_EXTRA_PRICE; ?>;
  function teamInit(){
    const list = document.getElementById('writer-list');
    const countInput = document.getElementById('writer_count');
    const feeOut = document.getElementById('team-total-fee');
    if (!list || !countInput || !feeOut) return;
    function syncInputs(n){
      const current = list.querySelectorAll('input[name="writers[]"]');
      const diff = n - current.length;
      if (diff > 0){
        for (let i=0;i<diff;i++){
          const inp = document.createElement('input');
          inp.type='text'; inp.name='writers[]'; inp.placeholder='Writer '+(current.length+i+1);
          inp.className='form-control';
          list.appendChild(inp);
        }
      } else if (diff < 0){
        for (let i=0; i<Math.abs(diff); i++){ list.lastElementChild && list.removeChild(list.lastElementChild); }
      }
    }
    function render(){
      const n = Math.max(1, Math.min(20, parseInt(countInput.value||String(BASE_INCLUDED),10)));
      syncInputs(n);
      const extra = Math.max(0, n-BASE_INCLUDED);
      const fee = BASE_PRICE + (extra*EXTRA_PRICE);
      feeOut.textContent = money(fee);
    }
    countInput.addEventListener('input', render);
    render();
  }
  window.updateOpenFee = function(){
    const list = document.getElementById('open-writer-list');
    const feeOut = document.getElementById('open-total-fee');
    if (!list || !feeOut) return;
    const rows = list.querySelectorAll('.cc-rowline');
    feeOut.textContent = money(rows.length * 10);
  }
  function openInit(){
    const list = document.getElementById('open-writer-list');
    const addBtn = document.getElementById('add-open-writer');
    if (!list || !addBtn) return;
    addBtn.addEventListener('click', ()=>{
      const row = document.createElement('div');
      row.className='cc-rowline';
      row.innerHTML = `
        <input class="form-control" type="text"  name="open_names[]"  placeholder="Name">
        <input class="form-control" type="email" name="open_emails[]" placeholder="Email">
        <input class="form-control" type="text"  name="open_phones[]" placeholder="Phone">
        <button type="button" class="btn outline" aria-label="Remove">Remove</button>
      `;
      row.querySelector('button').addEventListener('click', ()=>{ row.remove(); updateOpenFee(); });
      list.appendChild(row); updateOpenFee();
    });
    updateOpenFee();
  }
  document.addEventListener('DOMContentLoaded', function(){ teamInit(); openInit(); });
})();
</script>
</body>
</html>
