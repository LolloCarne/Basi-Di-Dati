<?php
require_once '../../includes/functions.php';
require_login();

// Qualsiasi utente loggato può finanziare (non solo creator)
$email_utente = $_SESSION['user_email'] ?? '';
$errors=[];$success=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nome_progetto = trim($_POST['nome_progetto']??'');
    $importo = trim($_POST['importo']??'');
    $codice_reward = trim($_POST['codice_reward']??''); // opzionale ma nella procedura è richiesto

    if ($nome_progetto==='') $errors[]='Nome progetto obbligatorio';
    if ($importo==='' || !is_numeric($importo) || $importo<=0) $errors[]='Importo non valido';
    if ($codice_reward==='') $errors[]='Codice reward obbligatorio (specificare una reward valida del progetto)';

    if (!$errors) {
        $mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI, DB_PORT_MYSQLI);
        if ($mysqli->connect_error) { $errors[]='Connessione DB fallita'; }
        else {
            $stmt = $mysqli->prepare('CALL FinanziaProgetto(?,?,?,?)');
            if(!$stmt){ $errors[]='Prepare fallita: '.htmlspecialchars($mysqli->error); }
            else {
                $importo_dec = (float)$importo;
                $stmt->bind_param('ssds',$email_utente,$nome_progetto,$importo_dec,$codice_reward);
                if($stmt->execute()) { $success='Finanziamento registrato.'; }
                else { $errors[]='Errore esecuzione: '.htmlspecialchars($stmt->error); }
                $stmt->close();
            }
            while ($mysqli->more_results() && $mysqli->next_result()) { $mysqli->use_result(); }
            $mysqli->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Finanzia Progetto</title></head>
<body>
<h2>Finanzia un Progetto</h2>
<?php if($success):?><p style="color:green;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<?php if($errors):?><ul style="color:red;"><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>';?></ul><?php endif; ?>
<form method="post">
<label>Nome Progetto<br><input name="nome_progetto" value="<?= htmlspecialchars($_POST['nome_progetto']??'') ?>" required></label><br><br>
<label>Importo (€)<br><input type="number" step="0.01" name="importo" value="<?= htmlspecialchars($_POST['importo']??'') ?>" required></label><br><br>
<label>Codice Reward associata<br><input name="codice_reward" value="<?= htmlspecialchars($_POST['codice_reward']??'') ?>" required></label><br><br>
<button type="submit">Finanzia</button>
</form>
<p><a href="../projects/search-view.php">Torna ai Progetti</a></p>
</body>
</html>
