<?php
// public/logout.php
session_start(); // Deve essere chiamata per poter accedere e distruggere la sessione
require_once __DIR__ . '/../includes/functions.php';
// Log logout prima di distruggere i dati
if(isset($_SESSION['user_email'])) {
    log_to_mongo('INFO','Logout utente',[ 'route'=>'/logout.php','ip'=>$_SERVER['REMOTE_ADDR']??null ], $_SESSION['user_email'], $_SESSION['user_nickname']??null);
}

// 1. Svuota l'array $_SESSION
$_SESSION = array();

// 2. Se si usano i cookie di sessione (pratica comune), cancellalo
// Nota: Questo distruggerà la sessione, non solo i dati di sessione!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Imposta un tempo nel passato
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Distruggi la sessione
session_destroy();

// 4. Reindirizza alla pagina di login o alla homepage
header("Location: login.php?loggedout=true"); // Aggiungi un parametro opzionale
exit;
?>