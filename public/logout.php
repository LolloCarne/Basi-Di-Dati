<?php
// public/logout.php
session_start(); // Deve essere chiamata per poter accedere e distruggere la sessione

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