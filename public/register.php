<?php
// public/register.php (Versione mysqli - Corretta)
session_start();

// ... (codice precedente per controllo sessione, errori, ecc.) ...

require_once '../config/database.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- 1. Recupero Dati Raw (Senza FILTER_SANITIZE_STRING) ---
    // Recupera direttamente da $_POST. La validazione e i prepared statements
    // si occuperanno della sicurezza per il DB. L'output escaping (htmlspecialchars)
    // si occuperà della sicurezza XSS quando mostri i dati.

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL); // FILTER_SANITIZE_EMAIL è ancora valido e utile
    $nickname = trim($_POST['nickname'] ?? ''); // Usa trim() per rimuovere spazi bianchi inizio/fine
    $password_raw = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $anno_nascita = filter_input(INPUT_POST, 'anno_nascita', FILTER_SANITIZE_NUMBER_INT); // OK per numeri
    $luogo_nascita = trim($_POST['luogo_nascita'] ?? '');

    // --- 2. Validazione ---
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "L'indirizzo email non è valido.";
    } else {
         // --- 3. Verifica Email e Nickname Univoci (mysqli) ---
         // ... (Codice per prepared statement di verifica - rimane invariato) ...
        $sql_check = "SELECT email FROM Utente WHERE email = ? OR nickname = ?";
        $stmt_check = $mysqli->prepare($sql_check);
        if ($stmt_check === false) {
            error_log("Errore preparazione query check: " . $mysqli->error);
            $errors['db'] = "Errore durante la verifica dei dati.";
        } else {
            $stmt_check->bind_param("ss", $email, $nickname); // Passa il nickname raw
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $existing_user = $result_check->fetch_assoc();
                if ($existing_user['email'] === $email) {
                    $errors['email'] = "Questo indirizzo email è già registrato.";
                } else {
                    $errors['nickname'] = "Questo nickname è già in uso.";
                }
            }
            $stmt_check->close();
        }
    }

    // Validazione aggiuntiva (es. campi obbligatori, lunghezza)
    if (empty($nickname)) $errors['nickname'] = "Il nickname è obbligatorio.";
    // Potresti aggiungere un controllo sulla lunghezza massima per il nickname
    // if (mb_strlen($nickname) > 50) $errors['nickname'] = "Il nickname è troppo lungo (max 50 caratteri).";

    if (strlen($password_raw) < 8) $errors['password'] = "La password deve essere di almeno 8 caratteri.";
    if ($password_raw !== $password_confirm) $errors['password_confirm'] = "Le password non coincidono.";

    if (empty($nome)) $errors['nome'] = "Il nome è obbligatorio.";
    // if (mb_strlen($nome) > 50) $errors['nome'] = "Il nome è troppo lungo (max 50 caratteri).";

    if (empty($cognome)) $errors['cognome'] = "Il cognome è obbligatorio.";
    // if (mb_strlen($cognome) > 50) $errors['cognome'] = "Il cognome è troppo lungo (max 50 caratteri).";

    // ... (altre validazioni se necessario) ...


    // --- 4. Se non ci sono errori, procedi con l'inserimento (mysqli) ---
    if (empty($errors)) {
        $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

        $sql_insert = "INSERT INTO Utente (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $mysqli->prepare($sql_insert);

        if ($stmt_insert === false) {
             // ... (gestione errore prepare) ...
        } else {
            $anno_nascita_int = $anno_nascita ? (int)$anno_nascita : null;

            // Passa le variabili raw (o validate) al bind_param. --- best practice contro sql injection
            // La libreria mysqli si occupa dell'escaping corretto per il DB.
            $stmt_insert->bind_param("sssssis",
                $email,
                $nickname,
                $password_hash,
                $nome,
                $cognome,
                $anno_nascita_int,
                $luogo_nascita 
            );

             // ... (execute, controllo successo, chiusura statement) ...
             if ($stmt_insert->execute()) {
                $successMessage = "Registrazione completata con successo! Ora puoi effettuare il login.";
                 $_POST = array(); // Svuota POST per il form
            } else {
                error_log("Errore esecuzione insert: " . $stmt_insert->error);
                $errors['db'] = "Errore durante il salvataggio dei dati.";
            }
            $stmt_insert->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione Bostarter</title>
    <link rel="stylesheet" href="css/style.css">
    <style> .error { color: red; font-size: 0.9em; } .success { color: green; } </style>
</head>
<body>
    <h1>Registrati Bostarter</h1>

    <?php if (!empty($successMessage)): ?>
        <p class="success"><?php echo $successMessage; ?></p>
    <?php endif; ?>

    <?php if (!empty($errors['db'])): ?>
        <p class="error"><?php echo $errors['db']; ?></p>
    <?php endif; ?>

    <form action="register.php" method="post">
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <?php if (isset($errors['email'])): ?><span class="error"><?php echo $errors['email']; ?></span><?php endif; ?>
        </div>
        <div>
            <label for="nickname">Nickname:</label>
            <input type="text" id="nickname" name="nickname" required value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : ''; ?>">
            <?php if (isset($errors['nickname'])): ?><span class="error"><?php echo $errors['nickname']; ?></span><?php endif; ?>
        </div>
        <div>
            <label for="password">Password (min. 8 caratteri):</label>
            <input type="password" id="password" name="password" required>
            <?php if (isset($errors['password'])): ?><span class="error"><?php echo $errors['password']; ?></span><?php endif; ?>
        </div>
        <div>
            <label for="password_confirm">Conferma Password:</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
            <?php if (isset($errors['password_confirm'])): ?><span class="error"><?php echo $errors['password_confirm']; ?></span><?php endif; ?>
        </div>
         <div>
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
             <?php if (isset($errors['nome'])): ?><span class="error"><?php echo $errors['nome']; ?></span><?php endif; ?>
        </div>
        <div>
            <label for="cognome">Cognome:</label>
            <input type="text" id="cognome" name="cognome" required value="<?php echo isset($_POST['cognome']) ? htmlspecialchars($_POST['cognome']) : ''; ?>">
             <?php if (isset($errors['cognome'])): ?><span class="error"><?php echo $errors['cognome']; ?></span><?php endif; ?>
        </div>
         <div>
            <label for="anno_nascita">Anno di nascita:</label>
            <input type="number" id="anno_nascita" name="anno_nascita" value="<?php echo isset($_POST['anno_nascita']) ? htmlspecialchars($_POST['anno_nascita']) : ''; ?>">
        </div>
         <div>
            <label for="luogo_nascita">Luogo di nascita:</label>
            <input type="text" id="luogo_nascita" name="luogo_nascita" value="<?php echo isset($_POST['luogo_nascita']) ? htmlspecialchars($_POST['luogo_nascita']) : ''; ?>">
        </div>
        <div>
            <button type="submit">Registrati</button>
        </div>
    </form>
    <p>Hai già un account? <a href="login.php">Accedi qui</a>.</p>
</body>
</html>