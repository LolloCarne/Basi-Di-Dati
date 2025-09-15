<?php
require_once '../../includes/functions.php';
log_to_mongo('INFO','Accesso lista reward progetto',[ 'project'=>($_GET['progetto']??null),'route'=>'/rewards/list_by_project.php','ip'=>$_SERVER['REMOTE_ADDR']??null ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
require_login();

$project = $_GET['progetto'] ?? null;
if(!$project){ http_response_code(400); echo 'Progetto mancante'; exit; }

$mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI);
if($mysqli->connect_error){ die('Connessione DB fallita'); }

$stmt = $mysqli->prepare('SELECT r.codice, r.descrizione, r.foto FROM Reward r JOIN RewardProgetto rp ON r.codice=rp.codice_reward WHERE rp.id_progetto=?');
$stmt->bind_param('s',$project); $stmt->execute(); $res = $stmt->get_result();
$rewards = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close(); $mysqli->close();
?>
<!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><title>Rewards</title></head><body>
<?php include_once __DIR__ . '/../../includes/topbar.php'; ?>
<h2>Rewards per <?= htmlspecialchars($project) ?></h2>
<?php if(empty($rewards)): ?>
  <p>Nessuna reward.</p>
<?php else: ?>
  <ul>
  <?php foreach($rewards as $r): ?>
    <li>
      <strong><?= htmlspecialchars($r['codice']) ?></strong>: <?= nl2br(htmlspecialchars($r['descrizione'])) ?><br>
      <?php if(!empty($r['foto'])): ?>
        <img src="<?= htmlspecialchars($r['foto']) ?>" alt="<?= htmlspecialchars($r['codice']) ?>" style="max-width:200px;max-height:200px"><br>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
</body></html>
