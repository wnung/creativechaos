<?php include __DIR__.'/_layout_top.php';
$pdo=cc_db();
$counts=[
  'registrations'=>$pdo->query("SELECT COUNT(*) c FROM registrations")->fetch()['c']??0,
  'submissions'=>$pdo->query("SELECT COUNT(*) c FROM submissions")->fetch()['c']??0,
  'authors'=>$pdo->query("SELECT COUNT(*) c FROM authors")->fetch()['c']??0,
];
?>
<div class="grid">
  <div class="card"><h3>Total Registrations</h3><div class="hero-number"><?=$counts['registrations']?></div></div>
  <div class="card"><h3>Total Submissions</h3><div class="hero-number"><?=$counts['submissions']?></div></div>
  <div class="card"><h3>Author Applicants</h3><div class="hero-number"><?=$counts['authors']?></div></div>
</div>
<?php include __DIR__.'/_layout_bottom.php'; ?>
