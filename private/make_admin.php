<?php
require_once '../config/database.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email_utente = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $codice_sicurezza_raw = trim($_POST['codice']);

    if (!$email_utente) {
        die("Indirizzo email non valido.");
    }

    //check del formato del codice di sicurezza (123-456-789)
    if (!preg_match('/^\d{3}-\d{3}-\d{3}$/', $codice_sicurezza_raw)) {
        die("Formato codice di sicurezza non valido. Utilizzare il formato 123-456-789.");
    }

    $securityCodeHash = password_hash($codice_sicurezza_raw, PASSWORD_DEFAULT);
    if ($securityCodeHash === false) {
        die("Errore durante la generazione dell'hash del codice di sicurezza.");
    }

    $stmt = $mysqli->prepare("CALL PromuoviUtenteAdAdmin(?, ?)");
    if ($stmt === false) {
        die("Errore preparazione chiamata procedura: " . $mysqli->error);
    }

    //Binda i parametri (email e hash)
    $stmt->bind_param("ss", $email_utente, $securityCodeHash);

    if ($stmt->execute()) {
        echo "Procedura eseguita con successo (utente promosso ad admin).";
    } else {
        echo "Errore durante l'esecuzione della procedura: " . $stmt->error;
    }

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
