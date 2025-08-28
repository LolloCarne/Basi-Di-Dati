<?php
require_once '../includes/functions.php';
require_login();

$mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI, DB_PORT_MYSQLI);
if($mysqli->connect_error){ die('Connessione fallita'); }
$mysqli->set_charset(DB_CHARSET_MYSQLI);

function runProc(mysqli $m, string $call){
  $rows=[]; if($res=$m->query($call)){ while($r=$res->fetch_assoc()) $rows[]=$r; $res->free(); }
  while($m->more_results() && $m->next_result()){ if($r=$m->use_result()){ $r->free(); } }
  return $rows;
}

$creatori = runProc($mysqli, 'CALL CreatoriAffidabili()');
$vicini   = runProc($mysqli, 'CALL ProgettiViciniBudget()');
$topFund  = runProc($mysqli, 'CALL TopUtentiFinanziatori()');
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Statistiche</title>
<style>
section{margin-bottom:2rem;} table{border-collapse:collapse;} th,td{border:1px solid #999;padding:4px 8px;}
</style>
</head>
<body>
<h1>Statistiche Piattaforma</h1>
<section>
  <h2>Top 3 Creatori per Affidabilità</h2>
  <?php if(!$creatori) echo '<p>Nessun dato.</p>'; else { echo '<table><tr><th>Nickname</th><th>Affidabilità %</th></tr>'; foreach($creatori as $c){ echo '<tr><td>'.htmlspecialchars($c['nickname']).'</td><td>'.number_format($c['affidabilita_percentuale'],2,',','.').'</td></tr>'; } echo '</table>'; } ?>
</section>
<section>
  <h2>Progetti Aperti più Vicini al Completamento</h2>
  <?php if(!$vicini) echo '<p>Nessun dato.</p>'; else { echo '<table><tr><th>Nome</th><th>Budget</th><th>Totale Finanziamenti</th><th>Differenza</th></tr>'; foreach($vicini as $p){ echo '<tr><td>'.htmlspecialchars($p['nome']).'</td><td>€'.number_format($p['budget'],2,',','.').'</td><td>€'.number_format($p['totale_finanziamenti'],2,',','.').'</td><td>€'.number_format($p['differenza'],2,',','.').'</td></tr>'; } echo '</table>'; } ?>
</section>
<section>
  <h2>Top 3 Utenti Finanziatori</h2>
  <?php if(!$topFund) echo '<p>Nessun dato.</p>'; else { echo '<table><tr><th>Nickname</th><th>Totale Finanziato</th></tr>'; foreach($topFund as $u){ echo '<tr><td>'.htmlspecialchars($u['nickname']).'</td><td>€'.number_format($u['totale_finanziato'],2,',','.').'</td></tr>'; } echo '</table>'; } ?>
</section>
<p><a href="index.php">Home</a></p>
</body>
</html>
