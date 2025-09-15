<?php
// public/admin_login.php (Versione mysqli)
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_email']) && isset($_SESSION['is_admin_verified']) && $_SESSION['is_admin_verified'] === true) {
    // Già loggato E verificato come admin
    header('Location: index.php');
    exit;
}

$error = '';
require_once '../config/database.php'; // $mysqli

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password_raw = $_POST['password'] ?? '';
    $security_code_raw = $_POST['security_code'] ?? ''; // Codice sicurezza in chiaro

    if (empty($email) || empty($password_raw) || empty($security_code_raw)) {
        $error = 'Email, password e codice di sicurezza sono obbligatori.';
        log_to_mongo('WARN', 'Campi login admin mancanti', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'route' => '/admin_login.php',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST'
        ], null, null);
    } else {
        // Cerca l'utente per email. Deve avere un codice sicurezza non nullo.
        $sql = "SELECT email, nickname, password, nome, cognome, codice_sicurezza
                FROM Utente
                WHERE email = ? AND codice_sicurezza IS NOT NULL"; // Assicurati che sia un potenziale admin
        $stmt = $mysqli->prepare($sql);

        if ($stmt === false) {
             error_log("Errore preparazione query login admin: " . $mysqli->error);
             $error = 'Si è verificato un errore durante il login admin (P).';
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                // Verifica SIA la password principale SIA il codice di sicurezza (entrambi hash)
                if (password_verify($password_raw, $admin['password']) &&
                    password_verify($security_code_raw, $admin['codice_sicurezza']))
                {
                    // Login Amministratore Riuscito e VERIFICATO!

                        // Log admin login riuscito
                        log_to_mongo('INFO', 'Admin loggato con successo', [
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                            'route' => '/admin_login.php',
                            'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST'
                        ], $admin['email'], $admin['nickname']);

                    session_regenerate_id(true);
                    $_SESSION['user_email'] = $admin['email'];
                    $_SESSION['user_nickname'] = $admin['nickname'];
                    $_SESSION['user_nome'] = $admin['nome'];
                    $_SESSION['user_cognome'] = $admin['cognome'];
                    $_SESSION['user_ruolo'] = 'admin'; // È sicuramente admin
                    $_SESSION['is_admin_verified'] = true; // Flag specifico per admin verificato

                    // Controlla se è anche creatore (opzionale, per info)
                    $sql_creator = "SELECT utente_email FROM Creatore WHERE utente_email = ?";
                    $stmt_creator = $mysqli->prepare($sql_creator);
                    if($stmt_creator){
                        $stmt_creator->bind_param("s", $admin['email']);
                        $stmt_creator->execute();
                        if ($stmt_creator->get_result()->num_rows > 0) {
                           $_SESSION['is_also_creator'] = true;
                        }
                        $stmt_creator->close();
                    }

                    header('Location: index.php');
                    exit;

                } else {
                    // Password o codice sicurezza errati
                    $error = 'Credenziali amministratore non valide o codice di sicurezza errato.';
                    log_to_mongo('WARN', 'Tentativo admin login fallito: credenziali errate', [
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'route' => '/admin_login.php',
                        'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                        'email_attempt' => $email
                    ], null, $email);
                }
            } else {
                // Utente non trovato o non è un admin (codice sicurezza nullo)
                 $error = 'Credenziali amministratore non valide.';
                 log_to_mongo('WARN', 'Tentativo admin login fallito: utente non trovato o non admin', [
                     'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                     'route' => '/admin_login.php',
                     'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                     'email_attempt' => $email
                 ], null, $email);
            }
            $stmt->close();
        }
    }
}
// $mysqli->close(); // Gestire globalmente
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login Amministratore</title>
    <link rel="stylesheet" href="css/style.css">
    <style> .error { color: red; } </style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/topbar.php'; ?>
    <h1>Login Amministratore</h1>

    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="admin_login.php" method="post">
        <div>
            <label for="email">Email Admin:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Password Admin:</label>
            <input type="password" id="password" name="password" required>
        </div>
         <div>
            <label for="security_code">Codice di Sicurezza:</label>
            <input type="password" id="security_code" name="security_code" required>
        </div>
        <div>
            <button type="submit">Login Admin</button>
        </div>
    </form>
     <p><a href="login.php">Sei un utente normale? Accedi qui</a>.</p>
</body>
</html>