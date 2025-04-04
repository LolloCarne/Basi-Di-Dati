<?php
require_once '../config/database.php'; // $mysqli è disponibile

// Se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recupera e pulisce i dati in ingresso
    $email_utente = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $codice_sicurezza_raw = trim($_POST['codice']);

    if (!$email_utente) {
        die("Indirizzo email non valido.");
    }

    // 1. Valida il formato del codice raw (opzionale ma consigliato)
    if (!preg_match('/^\d{3}-\d{3}-\d{3}$/', $codice_sicurezza_raw)) {
        die("Formato codice di sicurezza non valido. Utilizzare il formato 123-456-789.");
    }

    // 2. Genera l'hash del codice di sicurezza raw
    $securityCodeHash = password_hash($codice_sicurezza_raw, PASSWORD_DEFAULT);
    if ($securityCodeHash === false) {
        die("Errore durante la generazione dell'hash del codice di sicurezza.");
    }

    // 3. Prepara la chiamata alla stored procedure
    $stmt = $mysqli->prepare("CALL PromuoviUtenteAdAdmin(?, ?)");
    if ($stmt === false) {
        die("Errore preparazione chiamata procedura: " . $mysqli->error);
    }

    // 4. Binda i parametri (email e hash)
    $stmt->bind_param("ss", $email_utente, $securityCodeHash);

    // 5. Esegui la chiamata
    if ($stmt->execute()) {
        echo "Procedura eseguita con successo (utente promosso ad admin).";
        // Nota: La procedura stessa potrebbe aver generato un errore se l'utente non esiste.
    } else {
        echo "Errore durante l'esecuzione della procedura: " . $stmt->error;
    }

    // 6. Chiudi lo statement
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Promuovi Utente ad Admin</title>
</head>
<body>
    <h1>Promuovi Utente ad Admin</h1>
    <form method="post" action="">
        <label for="email">Email Utente:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <label for="codice">Codice di Sicurezza (formato 123-456-789):</label><br>
        <input type="text" id="codice" name="codice" pattern="\d{3}-\d{3}-\d{3}" placeholder="123-456-789" required><br><br>

        <input type="submit" value="Promuovi">
    </form>
</body>
</html>
