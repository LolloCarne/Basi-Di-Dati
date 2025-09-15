
<?php
require_once '../includes/functions.php'; 
require_login(); 

$nickname = htmlspecialchars($_SESSION['user_nickname']); 
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profilo Utente</title>
</head>
<body>
<?php include_once __DIR__ . '/../includes/topbar.php'; ?>
    <h1>Profilo di <?php echo $nickname; ?></h1>
    <p>Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
    <p>Ruolo: <?php echo htmlspecialchars($_SESSION['user_ruolo']); ?></p>
    <p>Nome: <?php echo $_SESSION['user_nome']; ?></p>
    <p>Cognome: <?php echo $_SESSION['user_cognome']; ?></p>
    <p><a href="logout.php">Logout</a></p>

    
    <p><a href="skill.php">Gestisci le tue Skill</a></p>
    
</body>
</html>
