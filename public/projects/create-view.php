<?php
require_once '../../includes/functions.php'; // importa le tue funzioni

require_login(); // verifica che sia loggato

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
        echo "<p>Progetto inserito correttamente!</p>";
    } else {
        echo "<p>Errore nell'inserimento: " . htmlspecialchars($stmt->error) . "</p>";
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
<form method="POST">
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

    <button type="submit">Inserisci Progetto</button>
</form>
</body>
</html>
        
