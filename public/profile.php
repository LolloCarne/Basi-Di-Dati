<?php
require_once '../includes/functions.php'; // Include le funzioni (che avvia anche la sessione)

require_login(); // Se non loggato, viene reindirizzato a login.php

// Da qui in poi l'utente è loggato.
//accedere ai dati in $_SESSION
//$user_id = $_SESSION['user_id'];
$nickname = htmlspecialchars($_SESSION['user_nickname']); // Usa htmlspecialchars per sicurezza!

?>
<!DOCTYPE html>
<html>
<head><title>Profilo Utente</title></head>
<body>
    <h1>Profilo di <?php echo $nickname; ?></h1>
    <p>Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
    <p>Ruolo: <?php echo htmlspecialchars($_SESSION['user_ruolo']); ?></p>
    <p><a href="logout.php">Logout</a></p>
    <?php // Qui aggiungerai il form per modificare i dati, le skill, etc. ?>
</body>
</html>