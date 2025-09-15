<?php
require_once '../../includes/functions.php';
log_to_mongo('INFO','Accesso modifica reward',[ 'reward'=>($_GET['codice']??null),'project'=>($_GET['progetto']??null),'route'=>'/rewards/modify.php','ip'=>$_SERVER['REMOTE_ADDR']??null ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
require_login();
if(!check_permission(['creator','admin'], true)) { http_response_code(403); echo 'Accesso negato'; exit; }

 $codice = $_GET['codice'] ?? null;
 $project = $_GET['progetto'] ?? null;
 if(!$codice || !$project){ http_response_code(400); echo 'Parametri mancanti'; exit; }

 $upload_dir = __DIR__ . '/../../uploads/rewards/';
 if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);

 // Verifica ownership: solo creatore del progetto (o admin) può modificare
 $current = $_SESSION['user_email'] ?? null;
 $chk = $mysqli->prepare('SELECT creatore_email, stato FROM Progetto WHERE nome=?');
 if($chk){ $chk->bind_param('s',$project); $chk->execute(); $chk->bind_result($creatore_email,$progetto_stato); $chk->fetch(); $chk->close(); }
 if(!isset($creatore_email)) { $mysqli->close(); http_response_code(404); echo 'Progetto non trovato'; exit; }
 if($current !== $creatore_email && !check_permission(['admin'], true)) { $mysqli->close(); http_response_code(403); echo 'Non autorizzato a modificare reward per questo progetto'; exit; }
function sanitize_filename($name){ $name = preg_replace('/[^A-Za-z0-9._-]/','_', $name); return $name; }

$errors=[]; $success='';

$mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI);
if($mysqli->connect_error) { die('Connessione DB fallita'); }
$stmt = $mysqli->prepare('SELECT descrizione,foto FROM Reward WHERE codice=?'); $stmt->bind_param('s',$codice); $stmt->execute(); $stmt->bind_result($descr,$foto); $stmt->fetch(); $stmt->close();

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['delete_foto'])){
        if($foto && file_exists(__DIR__ . '/../../' . ltrim($foto,'/'))){ unlink(__DIR__ . '/../../' . ltrim($foto,'/')); }
        $stmt = $mysqli->prepare('UPDATE Reward SET foto=NULL WHERE codice=?'); $stmt->bind_param('s',$codice); $stmt->execute(); $stmt->close();
    $success='Foto eliminata.'; $foto = null;
    log_to_mongo('INFO','Reward foto eliminata',[ 'reward'=>$codice,'project'=>$project ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
    }
    if(isset($_FILES['foto']) && $_FILES['foto']['error']!==UPLOAD_ERR_NO_FILE){
        $f = $_FILES['foto'];
        if($f['error']!==UPLOAD_ERR_OK){ $errors[]='Errore upload'; }
        else {
            $ext = pathinfo($f['name'],PATHINFO_EXTENSION);
            $safe = sanitize_filename($codice . '_' . time() . '.' . $ext);
            $dest = $upload_dir . $safe;
            if(move_uploaded_file($f['tmp_name'],$dest)){
                // elimina vecchia
                if($foto && file_exists(__DIR__ . '/../../' . ltrim($foto,'/'))){ unlink(__DIR__ . '/../../' . ltrim($foto,'/')); }
                $newpath = '/uploads/rewards/' . $safe;
                $stmt = $mysqli->prepare('UPDATE Reward SET foto=? WHERE codice=?'); $stmt->bind_param('ss',$newpath,$codice); $stmt->execute(); $stmt->close();
                $foto = $newpath; $success='Foto aggiornata.';
                log_to_mongo('INFO','Reward foto aggiornata',[ 'reward'=>$codice,'project'=>$project ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
            } else { $errors[]='Impossibile salvare file'; }
        }
    }
    if(isset($_POST['descrizione'])){
        $nd = trim($_POST['descrizione']);
        $stmt = $mysqli->prepare('UPDATE Reward SET descrizione=? WHERE codice=?'); $stmt->bind_param('ss',$nd,$codice); $stmt->execute(); $stmt->close();
    $descr = $nd; $success = $success ? $success . ' Descrizione aggiornata.' : 'Descrizione aggiornata.';
    log_to_mongo('INFO','Reward descrizione modificata',[ 'reward'=>$codice,'project'=>$project ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
    }
}

$mysqli->close();
?>
<!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><title>Modifica Reward</title></head><body>
<?php include_once __DIR__ . '/../../includes/topbar.php'; ?>
<h2>Modifica Reward <?= htmlspecialchars($codice) ?></h2>
<?php foreach($errors as $e) echo '<p style="color:red">'.htmlspecialchars($e).'</p>'; ?>
<?php if($success) echo '<p style="color:green">'.htmlspecialchars($success).'</p>'; ?>
<form method="post" enctype="multipart/form-data">
    <label>Descrizione:<br><textarea name="descrizione"><?= htmlspecialchars($descr) ?></textarea></label><br>
    <?php if($foto): ?>
        <div>Immagine attuale:<br><img src="<?= htmlspecialchars($foto) ?>" style="max-width:200px"></div>
        <label><input type="checkbox" name="delete_foto"> Elimina immagine attuale</label><br>
    <?php endif; ?>
    <label>Carica nuova immagine: <input type="file" name="foto" accept="image/*"></label><br>
    <button type="submit">Salva</button>
    <a href="list_by_project.php?progetto=<?= urlencode($project) ?>">Annulla</a>
</form>
</body></html>
