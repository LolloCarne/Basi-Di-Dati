<?php
// public/login.php (Versione mysqli)
// DEBUG temporaneo: mostra errori per diagnosticare pagina bianca
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Autoload Composer (necessario per MongoDB Client e altre dipendenze)
require_once __DIR__ . '/../vendor/autoload.php';

// La sessione verrà avviata da functions.php se non già avviata
require_once __DIR__ . '/../includes/functions.php';

// Se l'utente è già loggato, reindirizzalo
if (isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Includi la connessione mysqli
require_once '../config/database.php'; // $mysqli è disponibile

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password_raw = $_POST['password'] ?? ''; // Password in chiaro

    if (empty($email) || empty($password_raw)) {
        $error = 'Email e password sono obbligatori.';
        // Log mancata submit
        log_to_mongo('WARN', 'Campi login mancanti', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'route' => '/login.php',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST'
        ], null, null);
    } else {
        // Cerca l'utente per email nella tabella Utente
        // Seleziona i campi necessari, incluso l'hash della password e il codice sicurezza
        $sql_user = "SELECT email, nickname, password, nome, cognome, codice_sicurezza
                     FROM Utente
                     WHERE email = ?";
        $stmt_user = $mysqli->prepare($sql_user);

        if ($stmt_user === false) {
            error_log("Errore preparazione query login user: " . $mysqli->error);
            $error = 'Si è verificato un errore durante il login (P). Riprova.';
        } else {
            $stmt_user->bind_param("s", $email);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();

            if ($result_user->num_rows === 1) {
                // Utente trovato
                $user = $result_user->fetch_assoc();

                // Verifica la password usando l'hash salvato nella colonna 'password'
                if (password_verify($password_raw, $user['password'])) {
                    // Password corretta! Ora determina il ruolo

                    $user_email = $user['email']; // Email verificata
                    $user_role = 'user'; // Ruolo di default

                    // 1. È un amministratore? (Ha un codice sicurezza non nullo?)
                    //    Nota: Il login admin separato verificherà il codice stesso.
                    //    Qui basta vedere se ESISTE per assegnare il ruolo admin preliminare.
                    $is_admin = (!empty($user['codice_sicurezza'])); // Potrebbe essere più complesso (es. verificato)

                    if ($is_admin) {
                        $user_role = 'admin';
                         // Se sei admin, potresti essere anche creatore, controlliamo dopo
                    }

                    // 2. È un creatore? Controlla la tabella Creatore
                    $sql_creator = "SELECT utente_email FROM Creatore WHERE utente_email = ?";
                    $stmt_creator = $mysqli->prepare($sql_creator);
                    if($stmt_creator){
                        $stmt_creator->bind_param("s", $user_email);
                        $stmt_creator->execute();
                        $result_creator = $stmt_creator->get_result();

                        if ($result_creator->num_rows > 0) {
                           // È un creatore. Se era già admin, rimane admin (ruolo più alto)
                           // Se non era admin, diventa creator.
                           if ($user_role !== 'admin') {
                               $user_role = 'creator';
                           }
                           // Potresti voler salvare un flag aggiuntivo in sessione se un admin è ANCHE creator
                           // $_SESSION['is_also_creator'] = true;
                        }
                        $stmt_creator->close();
                    } else {
                         error_log("Errore preparazione query check creatore: " . $mysqli->error);
                         // Non bloccare il login per questo, ma segnala l'errore
                    }


                    // Login riuscito!
                    // Log login riuscito
                    log_to_mongo('INFO', 'Utente loggato con successo', [
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'route' => '/login.php',
                        'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST'
                    ], $user['email'], $user['nickname']);
                    session_regenerate_id(true); // Sicurezza sessione

                    // Memorizza le informazioni nella sessione
                    $_SESSION['user_email'] = $user['email']; // Chiave principale ora è email
                    $_SESSION['user_nickname'] = $user['nickname'];
                    $_SESSION['user_nome'] = $user['nome'];
                    $_SESSION['user_cognome'] = $user['cognome'];
                    $_SESSION['user_ruolo'] = $user_role; // Memorizza il ruolo determinato!

                    // Reindirizza in base al ruolo (o a una dashboard comune)
                     if ($user_role === 'admin') {
                         // Importante: L'admin deve usare admin_login.php per verificare il codice!
                         // Questo login generale non dovrebbe far accedere un admin senza codice.
                         // Forse dovremmo impedire il login qui se $is_admin è true? O reindirizzare ad admin_login?
                         // Per ora, reindirizziamo a index, ma l'accesso alle pagine admin sarà bloccato senza sessione admin valida.
                         // Si potrebbe mostrare un messaggio: "Login riuscito, ma usa il login admin per accedere alle funzioni amministrative."
                          $error = "Login base riuscito. Per funzioni admin, usa il <a href='admin_login.php'>Login Amministratore</a>.";
                         // Non fare redirect qui se mostri l'errore/messaggio
                     } else {
                        // Reindirizza utenti normali e creatori
                        header('Location: index.php'); // O profile.php
                        exit;
                    }

                } else {
                    // Password errata
                    $error = 'Credenziali non valide.';
                    log_to_mongo('WARN', 'Tentativo login fallito: password errata', [
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'route' => '/login.php',
                        'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                        'email_attempt' => $email
                    ], null, $email);
                }
            } else {
                // Utente non trovato
                $error = 'Credenziali non valide.';
                log_to_mongo('WARN', 'Tentativo login fallito: utente non trovato', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'route' => '/login.php',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                    'email_attempt' => $email
                ], null, $email);
            }
            $stmt_user->close();
        }
    }
}
// $mysqli->close(); // Gestire globalmente
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login Bostarter</title>
    <link rel="stylesheet" href="css/style.css">
    <style> .error { color: red; } </style>
</head>
<body>
    <h1>Accedi a Bostarter</h1>

    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; // Nota: l'errore ora può contenere HTML (il link) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['registered']) && $_GET['registered'] === 'success'): ?>
        <p style="color: green;">Registrazione avvenuta con successo! Effettua il login.</p>
    <?php endif; ?>
     <?php if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true'): ?>
        <p style="color: blue;">Logout effettuato con successo.</p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>

    <p>Non hai un account? <a href="register.php">Registrati qui</a>.</p>
    <p>Sei un amministratore? <a href="admin_login.php">Accedi da qui</a>.</p>

</body>
</html>