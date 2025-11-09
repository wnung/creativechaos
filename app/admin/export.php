<?php
require_once __DIR__.'/../inc/functions.php';
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/db.php';
cc_require_login();
$pdo = cc_db();

// If a table is requested, stream CSV and exit BEFORE any HTML is sent.
if (isset($_GET['table'])) {
  $table = $_GET['table'];
  $allowed = ['registrations','registration_writers','submissions','authors'];
  if (!in_array($table, $allowed, true)) {
    http_response_code(400);
    exit('Invalid table');
  }

  // Prevent prior output from breaking headers
  if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) { ob_end_clean(); }
  }

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$table.'.csv"');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');

  $out = fopen('php://output', 'w');
  // Get columns and write header row
  $cols = $pdo->query("DESCRIBE `$table`")->fetchAll();
  fputcsv($out, array_column($cols, 'Field'));

  // Stream rows
  $stmt = $pdo->query("SELECT * FROM `$table`");
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, $row);
  }
  fclose($out);
  exit;
}

// Otherwise render the export UI
include __DIR__.'/_layout_top.php';
?>
<h3>Export CSV</h3>
<p class="help">Click to download a CSV of each table.</p>
<ul>
  <li><a class="btn" href="?table=registrations">Registrations CSV</a></li>
  <li><a class="btn" href="?table=registration_writers">Registration Writers CSV</a></li>
  <li><a class="btn" href="?table=submissions">Submissions CSV</a></li>
  <li><a class="btn" href="?table=authors">Authors CSV</a></li>
</ul>
<?php include __DIR__.'/_layout_bottom.php'; ?>
