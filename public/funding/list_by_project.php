<?php
require_once '../../includes/functions.php';
require_login();

$nome_progetto = $_GET['progetto'] ?? '';
$fundings=[];$errors=[];
if ($nome_progetto==='') { $errors[]='Parametro progetto mancante'; }
else {
    $mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI, DB_PORT_MYSQLI);
    if ($mysqli->connect_error) { $errors[]='Connessione DB fallita'; }
    else {
        $stmt=$mysqli->prepare('SELECT f.email_utente, f.data, f.importo, rf.codice_reward FROM Finanziamento f LEFT JOIN RewardFinanziamento rf ON rf.email_utente=f.email_utente AND rf.nome_progetto=f.nome_progetto AND rf.data=f.data WHERE f.nome_progetto=? ORDER BY f.data DESC');
        if(!$stmt){ $errors[]='Prepare fallita'; } else { $stmt->bind_param('s',$nome_progetto); $stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()){ $fundings[]=$row; } $stmt->close(); }
        $mysqli->close();
    }
}
?>
<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Finanziamenti Progetto</title></head><body>
<h2>Finanziamenti per progetto: <?= htmlspecialchars($nome_progetto) ?></h2>
<?php if($errors):?><ul style="color:red;"><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>';?></ul><?php endif; ?>
<?php if(!$errors): ?>
<?php if(!$fundings):?><p>Nessun finanziamento.</p><?php else: ?>
<table border="1" cellpadding="5"><tr><th>Email Utente</th><th>Data</th><th>Importo</th><th>Reward</th></tr>
<?php foreach($fundings as $f): ?>
<tr>
<td><?= htmlspecialchars($f['email_utente']) ?></td>
<td><?= htmlspecialchars($f['data']) ?></td>
<td>€<?= number_format($f['importo'],2,',','.') ?></td>
<td><?= htmlspecialchars($f['codice_reward']??'') ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>
<p><a href="fund.php">Finanzia un altro progetto</a></p>
<p><a href="../projects/search-view.php">Torna ai Progetti</a></p>
</body></html>
