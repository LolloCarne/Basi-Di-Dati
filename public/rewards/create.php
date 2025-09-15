<?php
require_once '../../includes/functions.php';
log_to_mongo('INFO','Accesso creazione reward',[ 'project'=>($_GET['progetto']??null),'route'=>'/rewards/create.php','ip'=>$_SERVER['REMOTE_ADDR']??null ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
require_login();

// Solo creator o admin possono creare rewards
if(!check_permission(['creator','admin'], true)) { http_response_code(403); echo 'Accesso negato'; exit; }

$project = $_GET['progetto'] ?? null;
if(!$project){ http_response_code(400); echo 'Progetto mancante'; exit; }

$errors = [];
// Verifica che l'utente sia creatore del progetto
$current = $_SESSION['user_email'] ?? null;
$mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI);
if($mysqli->connect_error){ die('Connessione DB fallita'); }
$chk = $mysqli->prepare('SELECT creatore_email, stato FROM Progetto WHERE nome=?');
if($chk){ $chk->bind_param('s',$project); $chk->execute(); $chk->bind_result($creatore_email,$progetto_stato); $chk->fetch(); $chk->close(); }
if(!isset($creatore_email)) { $mysqli->close(); http_response_code(404); echo 'Progetto non trovato'; exit; }
if($current !== $creatore_email && !check_permission(['admin'], true)) { $mysqli->close(); http_response_code(403); echo 'Non autorizzato a creare reward per questo progetto'; exit; }

$success = '';

// upload dir
$upload_dir = __DIR__ . '/../../uploads/rewards/';
if(!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

function sanitize_filename($name){ $name = preg_replace('/[^A-Za-z0-9._-]/','_', $name); return $name; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $codice = trim($_POST['codice'] ?? '');
    $descr = trim($_POST['descrizione'] ?? '');
    if($codice===''){ $errors[]='Codice obbligatorio'; }
    if($descr===''){ $errors[]='Descrizione obbligatoria'; }

    $foto_path = null;
    if(isset($_FILES['foto']) && $_FILES['foto']['error']!==UPLOAD_ERR_NO_FILE){
        $f = $_FILES['foto'];
        if($f['error']!==UPLOAD_ERR_OK){ $errors[]='Errore upload file'; }
        else {
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $safe = sanitize_filename($codice . '_' . time() . '.' . $ext);
            $dest = $upload_dir . $safe;
            if(move_uploaded_file($f['tmp_name'], $dest)){
                // store web path relative
                $foto_path = '/uploads/rewards/' . $safe;
            } else { $errors[]='Impossibile salvare file'; }
        }
    }

    if(empty($errors)){
        $mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI);
        if($mysqli->connect_error){ $errors[]='Connessione DB fallita'; }
        else {
            $stmt = $mysqli->prepare('INSERT INTO Reward(codice, descrizione, foto) VALUES(?,?,?)');
            $stmt->bind_param('sss',$codice,$descr,$foto_path);
            if(!$stmt->execute()){
                $errors[]='Errore DB: '.htmlspecialchars($stmt->error);
            } else {
                // associare a progetto
                $stmt2 = $mysqli->prepare('INSERT INTO RewardProgetto(id_progetto, codice_reward) VALUES(?,?)');
                $stmt2->bind_param('ss',$project,$codice); $stmt2->execute(); $stmt2->close();
                $success = 'Reward creata.';
                log_to_mongo('INFO','Reward creata',[ 'project'=>$project,'code'=>$codice,'has_image'=> (bool)$foto_path ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
            }
            $stmt->close(); $mysqli->close();
        }
    }
}
?>
<!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><title>Crea Reward</title></head><body>
<?php include_once __DIR__ . '/../../includes/topbar.php'; ?>
<h2>Crea Reward per <?= htmlspecialchars($project) ?></h2>
<?php foreach($errors as $e) echo '<p style="color:red">'.htmlspecialchars($e).'</p>'; ?>
<?php if($success) echo '<p style="color:green">'.htmlspecialchars($success).'</p>'; ?>
<form method="post" enctype="multipart/form-data">
    <label>Codice: <input name="codice" required></label><br>
    <label>Descrizione:<br><textarea name="descrizione" required></textarea></label><br>
    <label>Foto: <input type="file" name="foto" accept="image/*"></label><br>
    <button type="submit">Crea</button>
    <a href="list_by_project.php?progetto=<?= urlencode($project) ?>">Annulla</a>
</form>
</body></html>
