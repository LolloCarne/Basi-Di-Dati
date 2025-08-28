<?php
require_once '../includes/functions.php';
require_login();
require_permission('admin', true, 'die');

$mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI, DB_PORT_MYSQLI);
if($mysqli->connect_error){ die('Connessione fallita'); }
$mysqli->set_charset(DB_CHARSET_MYSQLI);

$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $competenza = trim($_POST['competenza'] ?? '');
  if($competenza===''){ $err='Nome competenza obbligatorio'; }
  else {
    $stmt=$mysqli->prepare('INSERT INTO Skill(competenza) VALUES (?)');
    if(!$stmt){ $err='Prepare fallita'; }
    else { $stmt->bind_param('s',$competenza); if($stmt->execute()){ $msg='Competenza inserita.'; } else { $err='Errore inserimento: '.htmlspecialchars($stmt->error); } $stmt->close(); }
  }
}
$skills=[]; if($res=$mysqli->query('SELECT id, competenza FROM Skill ORDER BY competenza')){ while($r=$res->fetch_assoc()) $skills[]=$r; $res->free(); }
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Gestione Competenze</title></head>
<body>
<h1>Gestione Competenze (Admin)</h1>
<?php if($msg) echo '<p style="color:green;">'.htmlspecialchars($msg).'</p>'; if($err) echo '<p style="color:red;">'.htmlspecialchars($err).'</p>'; ?>
<form method="post">
  <label>Nuova Competenza<br><input name="competenza" required></label>
  <button type="submit">Inserisci</button>
</form>
<h2>Elenco</h2>
<ul>
<?php foreach($skills as $s){ echo '<li>'.htmlspecialchars($s['competenza']).'</li>'; } ?>
</ul>
<p><a href="index.php">Home</a></p>
</body>
</html>
