<?php
// skill.php
require_once '../includes/functions.php';
require_login();

// Includi la connessione al database
require_once '../config/database.php';

$user_email = $_SESSION['user_email'];
$error = '';

// Gestione delle richieste POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {

        // Aggiungi o aggiorna la skill per l'utente
        if ($_POST['action'] === 'aggiungi') {
            $skill_id = intval($_POST['skill_id']);
            $livello = intval($_POST['livello']);

            $stmt = $mysqli->prepare("CALL AddUpdateUtenteSkill(?, ?, ?)");
            if (!$stmt) {
                die("Errore nella preparazione della query: " . $mysqli->error);
            }
            $stmt->bind_param("sii", $user_email, $skill_id, $livello);
            $stmt->execute();
            $stmt->close();
            $mysqli->next_result();
        }

        // Eliminazione della skill per l'utente
        if ($_POST['action'] === 'elimina') {
            $skill_id = intval($_POST['skill_id']);

            $stmt = $mysqli->prepare("CALL DeleteUtenteSkill(?, ?)");
            if (!$stmt) {
                die("Errore nella preparazione della query: " . $mysqli->error);
            }
            $stmt->bind_param("si", $user_email, $skill_id);
            $stmt->execute();
            $stmt->close();
            $mysqli->next_result();
        }

        // Modifica del livello della skill (può essere gestita come "aggiungi" visto che la procedura gestisce insert/update)
        if ($_POST['action'] === 'modifica') {
            $skill_id = intval($_POST['skill_id']);
            $livello = intval($_POST['livello']);

            $stmt = $mysqli->prepare("CALL AddUpdateUtenteSkill(?, ?, ?)");
            if (!$stmt) {
                die("Errore nella preparazione della query: " . $mysqli->error);
            }
            $stmt->bind_param("sii", $user_email, $skill_id, $livello);
            $stmt->execute();
            $stmt->close();
            $mysqli->next_result();
        }
    }
}

// Recupera l'elenco completo delle skill disponibili per la tendina
$all_skills = [];
$query_all = "SELECT id, competenza FROM Skill ORDER BY competenza";
if ($result = $mysqli->query($query_all)) {
    while ($row = $result->fetch_assoc()) {
        $all_skills[] = $row;
    }
    $result->free();
}

// Recupera le skill associate all'utente tramite la stored procedure
$stmt = $mysqli->prepare("CALL GetUtenteSkill(?)");
if (!$stmt) {
    die("Errore nella preparazione della query: " . $mysqli->error);
}
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

$user_skills = [];
while ($row = $result->fetch_assoc()) {
    $user_skills[] = $row;
}
$stmt->close();
$mysqli->next_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestione Skill</title>
</head>
<body>
    <h1>Gestisci le tue Skill</h1>

    <!-- Form per aggiungere o aggiornare una Skill -->
    <h2>Aggiungi / Aggiorna Skill</h2>
    <form method="post" action="skill.php">
        <input type="hidden" name="action" value="aggiungi">
        <div>
            <label for="skill_id">Skill:</label>
            <select name="skill_id" id="skill_id" required>
                <option value="">Seleziona una skill</option>
                <?php foreach ($all_skills as $skill): ?>
                    <option value="<?php echo $skill['id']; ?>">
                        <?php echo htmlspecialchars($skill['competenza']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="livello">Livello (0-5):</label>
            <input type="number" id="livello" name="livello" min="0" max="5" required>
        </div>
        <button type="submit">Aggiungi / Aggiorna Skill</button>
    </form>

    <!-- Visualizza le skill associate all'utente -->
    <h2>Le tue Skill</h2>
    <table border="1">
        <tr>
            <th>Skill</th>
            <th>Livello</th>
            <th>Azione</th>
        </tr>
        <?php foreach ($user_skills as $us): ?>
            <tr>
                <td><?php echo htmlspecialchars($us['skill_name']); ?></td>
                <td><?php echo htmlspecialchars($us['livello']); ?></td>
                <td>
                    <!-- Form per eliminare la skill -->
                    <form method="post" action="skill.php" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questa skill?');">
                        <input type="hidden" name="action" value="elimina">
                        <input type="hidden" name="skill_id" value="<?php echo htmlspecialchars($us['skill_id']); ?>">
                        <button type="submit">Elimina</button>
                    </form>
                    <!-- Form per modificare il livello della skill -->
                    <form method="post" action="skill.php" style="display:inline;">
                        <input type="hidden" name="action" value="modifica">
                        <input type="hidden" name="skill_id" value="<?php echo htmlspecialchars($us['skill_id']); ?>">
                        <input type="number" name="livello" value="<?php echo htmlspecialchars($us['livello']); ?>" min="0" max="5" required>
                        <button type="submit">Modifica</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p><a href="profile.php">Torna al Profilo</a></p>
</body>
</html>
