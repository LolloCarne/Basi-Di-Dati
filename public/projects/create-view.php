<?php
require_once '../../includes/functions.php'; // importa le tue funzioni

require_login(); // verifica che sia loggato
log_to_mongo('INFO','Accesso form creazione progetto',[ 'route'=>'/projects/create-view.php','method'=>$_SERVER['REQUEST_METHOD']??'GET','ip'=>$_SERVER['REMOTE_ADDR']??null ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);

$utente_autorizzato = check_permission(['creator', 'admin'], true);

// Se ha cliccato sul bottone "Visualizza Progetti"
if (isset($_GET['action']) && $_GET['action'] === 'lista') {

    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "bostarter";

    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }

    // Esegui la procedura VisualizzaProgetti
    $result = $conn->query("CALL VisualizzaProgetti()");

    if (!$result) {
        die("Errore nella query: " . $conn->error);
    }
    ?>

    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Elenco Progetti</title>
    </head>
    <body>
<?php include_once __DIR__ . '/../../includes/topbar.php'; ?>

    <h2>Lista dei Progetti</h2>
    <ul>
        <?php
        while ($row = $result->fetch_assoc()) {
            echo '<li>' . htmlspecialchars($row['nome']) . '</li>';
        }
        ?>
    </ul>

    <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Torna Indietro</a>

    </body>
    </html>

    <?php
    $result->free();
    $conn->close();
    exit;
}

// Se l'utente NON è autorizzato, mostra il bottone per la lista progetti
if (!$utente_autorizzato) {
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Visualizza Progetto</title>
    </head>
    <body>

    <h2>Non hai i permessi per inserire un progetto</h2>
    <p>Solo utenti con ruolo <strong>Creator</strong> o <strong>Admin verificato</strong> possono aggiungere nuovi progetti.</p>

    <form method="get">
        <button type="submit" name="action" value="lista">Visualizza tutti i progetti</button>
    </form>

    </body>
    </html>
    <?php
    exit;
}

// Se l'utente ha i permessi, mostra il form per inserire progetti
$host = "localhost";
$user = "root";
$password = "";
$database = "bostarter";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST['nome'];
    $descrizione = $_POST['descrizione'];
    $budget = $_POST['budget'];
    $data_limite = $_POST['data_limite'];
    $stato = $_POST['stato'];
    $tipo_progetto = $_POST['tipo_progetto'];

    $creatore_email = $_SESSION['user_email'] ?? null;

    if (!$creatore_email) {
        die("Errore: email utente non trovata nella sessione.");
    }

    $stmt = $conn->prepare("CALL InserisciProgetto(?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
    die("Errore prepare: " . $conn->error);
        }

        $stmt->bind_param("sssdsss", $nome, $creatore_email, $descrizione, $budget, $data_limite, $stato, $tipo_progetto);

    if ($stmt->execute()) {
        log_to_mongo('INFO','Progetto creato',[ 'project'=>$nome,'budget'=>$budget,'deadline'=>$data_limite,'state'=>$stato,'type'=>$tipo_progetto ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
        if(!empty($_POST['reward_descrizione']) || (isset($_FILES['reward_foto']) && $_FILES['reward_foto']['error']!==UPLOAD_ERR_NO_FILE)){
            function generate_code($len=8){ $chars='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $s=''; for($i=0;$i<$len;$i++) $s .= $chars[random_int(0,strlen($chars)-1)]; return 'R'.$s; }
            $codice = generate_code(8);
            $rdescr = trim($_POST['reward_descrizione'] ?? '');
            $foto_path = null;
            $upload_dir = __DIR__ . '/../../uploads/rewards/'; if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
            if (isset($_FILES['reward_foto']) && $_FILES['reward_foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $f = $_FILES['reward_foto'];
                if ($f['error'] !== UPLOAD_ERR_OK) {
                    $msg = 'Errore upload immagine reward. Codice errore: ' . intval($f['error']) . '.';
                    switch ($f['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $msg .= ' File troppo grande.';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $msg .= ' Upload parziale.';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $msg .= ' Directory temporanea mancante sul server.';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $msg .= ' Impossibile scrivere il file su disco.';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $msg .= ' Estensione PHP ha bloccato l\'upload.';
                            break;
                        default:
                            $msg .= ' Errore sconosciuto.';
                    }
                    error_log('[UPLOAD] ' . $msg . ' _FILES info: ' . json_encode(['name'=>$f['name'],'tmp_name'=>$f['tmp_name']]));
                    $_SESSION['flash_upload_error'] = $msg;
                } else {
                    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                    $safe = preg_replace('/[^A-Za-z0-9._-]/','_', $codice . '_' . time() . '.' . $ext);
                    $dest = $upload_dir . $safe;
                    //il file proviene da upload HTTP?
                    if (!is_uploaded_file($f['tmp_name'])) {
                        $msg = 'Il file caricato non è riconosciuto come upload HTTP valido.';
                        error_log('[UPLOAD] ' . $msg . ' tmp_name: ' . $f['tmp_name']);
                        $_SESSION['flash_upload_error'] = $msg;
                    } else {
                        if (move_uploaded_file($f['tmp_name'], $dest)) {
                            $foto_path = '/uploads/rewards/' . $safe;
                        } else {
                            $msg = 'Move uploaded file fallito. Controllare permessi directory: ' . $upload_dir;
                            error_log('[UPLOAD] ' . $msg . ' src: ' . $f['tmp_name'] . ' dest: ' . $dest);
                            $_SESSION['flash_upload_error'] = $msg;
                        }
                    }
                }
            }
            $mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI);
            if(!$mysqli->connect_error){
                $st = $mysqli->prepare('INSERT INTO Reward(codice, descrizione, foto) VALUES(?,?,?)');
                $st->bind_param('sss',$codice,$rdescr,$foto_path); $st->execute(); $st->close();
                $st2 = $mysqli->prepare('INSERT INTO RewardProgetto(id_progetto, codice_reward) VALUES(?,?)'); $st2->bind_param('ss',$nome,$codice); $st2->execute(); $st2->close();
                $mysqli->close();
                log_to_mongo('INFO','Reward iniziale creata con progetto',[ 'project'=>$nome,'reward_code'=>$codice,'has_image'=> (bool)$foto_path ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
            }
        }
        // redirect alla ricerca progetti
        header('Location: ../projects/search-view.php');
        exit;
    } else {
        echo "<p>Errore nell'inserimento: " . htmlspecialchars($stmt->error) . "</p>";
        log_to_mongo('ERROR','Errore creazione progetto',[ 'error'=>$stmt->error??null ], $_SESSION['user_email']??null, $_SESSION['user_nickname']??null);
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Nuovo Progetto</title>
</head>
<body>

<h2>Inserisci Nuovo Progetto</h2>
<form method="POST" enctype="multipart/form-data">
    <label for="nome">Nome Progetto</label><br>
    <input type="text" id="nome" name="nome" required><br><br>

    <label for="descrizione">Descrizione</label><br>
    <textarea id="descrizione" name="descrizione" rows="4" required></textarea><br><br>

    <label for="budget">Budget (€)</label><br>
    <input type="number" step="0.01" id="budget" name="budget" required><br><br>

    <label for="data_limite">Data Limite</label><br>
    <input type="date" id="data_limite" name="data_limite" required><br><br>

    <label for="stato">Stato</label><br>
    <select id="stato" name="stato" required>
        <option value="aperto">Aperto</option>
        <option value="chiuso">Chiuso</option>
    </select><br><br>

    <label for="tipo_progetto">Tipo Progetto</label><br>
    <select id="tipo_progetto" name="tipo_progetto" required>
        <option value="hardware">Hardware</option>
        <option value="software">Software</option>
    </select><br><br>

    <h3>Opzionale: Reward iniziale</h3>
    <label for="reward_descrizione">Descrizione Reward</label><br>
    <textarea id="reward_descrizione" name="reward_descrizione" rows="3"></textarea><br><br>
    <label for="reward_foto">Foto Reward</label><br>
    <input type="file" id="reward_foto" name="reward_foto" accept="image/*"><br><br>

    <button type="submit">Inserisci Progetto</button>
</form>
</body>
</html>
        
