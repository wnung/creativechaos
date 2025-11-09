<?php include __DIR__.'/_layout_top.php'; $pdo=cc_db();
$type = isset($_GET['type']) && in_array($_GET['type'], ['team','open']) ? $_GET['type'] : null;
$where = $type ? "WHERE r.registration_type = :type" : "";
$sql = "SELECT s.*, r.registration_type, r.team_name, r.student_name, r.guardian_email,
        w.writer_name AS first_writer
        FROM submissions s
        JOIN registrations r ON r.id = s.registration_id
        LEFT JOIN (
          SELECT registration_id, MIN(id) AS min_id
          FROM registration_writers GROUP BY registration_id
        ) m ON m.registration_id = r.id
        LEFT JOIN registration_writers w ON w.id = m.min_id
        $where
        ORDER BY s.created_at DESC LIMIT 500";
$st = $pdo->prepare($sql);
if($type){ $st->bindValue(':type',$type); }
$st->execute();
$rows = $st->fetchAll();
?>
<h3>Submissions</h3>
<div class="flex" style="margin-bottom:8px">
  <a class="btn outline" href="submissions.php">All</a>
  <a class="btn outline" href="submissions.php?type=team">Teams</a>
  <a class="btn outline" href="submissions.php?type=open">Open Class</a>
</div>
<table class="table">
<thead><tr><th>ID</th><th>Type</th><th>Team/Writer</th><th>Guardian</th><th>Original Name</th><th>Notes</th><th>Uploaded</th><th>File</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?=$r['id']?></td>
<td><?=htmlspecialchars(strtoupper($r['registration_type']))?></td>
<td>
  <?php if($r['registration_type']==='team'){ echo htmlspecialchars($r['team_name']); }
        else { echo htmlspecialchars($r['first_writer'] ?: 'Open Class'); } ?>
</td>
<td><?=htmlspecialchars($r['guardian_email'])?></td>
<td><?=htmlspecialchars($r['original_name'])?></td>
<td><?=nl2br(htmlspecialchars($r['notes']))?></td>
<td><?=$r['created_at']?></td>
<td><a class="btn" href="download.php?id=<?=$r['id']?>">Download</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php include __DIR__.'/_layout_bottom.php'; ?>
