<?php include __DIR__.'/_layout_top.php'; $pdo=cc_db();
$rows=$pdo->query("SELECT * FROM authors ORDER BY created_at DESC LIMIT 500")->fetchAll();
?>
<h3>Author Applicants</h3>
<table class="table">
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Genre</th><th>Website</th><th>Notes</th><th>Created</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?=$r['id']?></td>
<td><?=htmlspecialchars($r['name'])?></td>
<td><?=htmlspecialchars($r['email'])?></td>
<td><?=htmlspecialchars($r['phone'])?></td>
<td><?=htmlspecialchars($r['genre'])?></td>
<td><?php if($r['website']){ ?><a href="<?=htmlspecialchars($r['website'])?>" target="_blank">Open</a><?php } ?></td>
<td><?=nl2br(htmlspecialchars($r['notes']))?></td>
<td><?=$r['created_at']?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php include __DIR__.'/_layout_bottom.php'; ?>
