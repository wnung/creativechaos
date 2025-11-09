<?php
require_once __DIR__.'/../inc/functions.php';
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/db.php';
cc_require_login();
$pdo = cc_db();

// Build ORDER BY from safe whitelist
$valid = ['type','name'];
$sort  = (isset($_GET['sort'])  && in_array($_GET['sort'],  $valid, true)) ? $_GET['sort']  : null;
$sort2 = (isset($_GET['sort2']) && in_array($_GET['sort2'], $valid, true)) ? $_GET['sort2'] : null;

$map = [
  'type' => "r.registration_type ASC",
  'name' => "COALESCE(NULLIF(r.team_name,''), NULLIF(w.writer_name,''), r.student_name) ASC"
];
$orderParts = [];
if ($sort && isset($map[$sort]))  $orderParts[] = $map[$sort];
if ($sort2 && isset($map[$sort2]) && $sort2 !== $sort) $orderParts[] = $map[$sort2];
$orderParts[] = "r.created_at DESC";
$order = "ORDER BY " . implode(", ", $orderParts);

// Fetch rows with first_writer via left-join
$sql = "SELECT r.*, w.writer_name AS first_writer
        FROM registrations r
        LEFT JOIN (
          SELECT registration_id, MIN(id) AS min_id
          FROM registration_writers
          GROUP BY registration_id
        ) m ON m.registration_id = r.id
        LEFT JOIN registration_writers w ON w.id = m.min_id
        $order
        LIMIT 500";
$rows = $pdo->query($sql)->fetchAll();

include __DIR__.'/_layout_top.php';
?>
<h3>Registrations</h3>
<div class="flex" style="margin-bottom:8px">
  <a class="btn outline" href="registrations.php">Default</a>
  <a class="btn outline" href="registrations.php?sort=type">Sort by Type</a>
  <a class="btn outline" href="registrations.php?sort=name">Sort by Team/Name</a>
  <a class="btn outline" href="registrations.php?sort=type&sort2=name">Type → Name</a>
  <a class="btn outline" href="registrations.php?sort=name&sort2=type">Name → Type</a>
</div>
<p class="small">Active sort: <strong><?=htmlspecialchars($sort ?: 'created_at')?></strong><?php if($sort2): ?>, then <strong><?=htmlspecialchars($sort2)?></strong><?php endif; ?></p>

<table class="table">
<thead><tr>
<th>ID</th>
<th>Type</th>
<th>Team / First Writer</th>
<th>School</th>
<th>Guardian</th>
<th>Writers</th>
<th>Extra</th>
<th>Fee</th>
<th>Created</th>
</tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?=$r['id']?></td>
<td><?=htmlspecialchars(strtoupper($r['registration_type']))?></td>
<td><?php if($r['registration_type']==='team'){ echo htmlspecialchars($r['team_name']); } else { echo htmlspecialchars($r['first_writer'] ?: 'Open Class'); } ?></td>
<td><?=htmlspecialchars($r['school'])?></td>
<td><?=htmlspecialchars($r['guardian_email'])?></td>
<td><?=$r['writer_count']?></td>
<td><?=$r['extra_writers']?></td>
<td>$<?=number_format((float)$r['fee'],2)?></td>
<td><?=$r['created_at']?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4>Team Writer Details</h4>
<table class="table">
<thead><tr><th>Reg ID</th><th>Writer Name</th><th>Email</th><th>Phone</th></tr></thead>
<tbody>
<?php
foreach($pdo->query("SELECT registration_id, writer_name, writer_email, writer_phone FROM registration_writers ORDER BY registration_id, id") as $w){
  echo '<tr><td>'.$w['registration_id'].'</td><td>'.htmlspecialchars($w['writer_name']).'</td><td>'.htmlspecialchars($w['writer_email']).'</td><td>'.htmlspecialchars($w['writer_phone']).'</td></tr>';
}
?>
</tbody></table>

<?php include __DIR__.'/_layout_bottom.php'; ?>
