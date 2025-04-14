<!-- profilo.php -->
<?php
require_once '../includes/functions.php'; // Include le funzioni (che avvia anche la sessione)
require_login(); // Se non loggato, viene reindirizzato a login.php

$nickname = htmlspecialchars($_SESSION['user_nickname']); // Usa htmlspecialchars per sicurezza!
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profilo Utente</title>
</head>
<body>
    <h1>Profilo di <?php echo $nickname; ?></h1>
    <p>Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
    <p>Ruolo: <?php echo htmlspecialchars($_SESSION['user_ruolo']); ?></p>
    <p>Nome: <?php echo $_SESSION['user_nome']; ?></p>
    <p>Cognome: <?php echo $_SESSION['user_cognome']; ?></p>
    <p><a href="logout.php">Logout</a></p>

    <!-- Link alla pagina per gestire le Skill -->
    <p><a href="skill.php">Gestisci le tue Skill</a></p>
    
</body>
</html>
