<?php

session_start(); 
require_once __DIR__ . '/../includes/functions.php';

if(isset($_SESSION['user_email'])) {
    log_to_mongo('INFO','Logout utente',[ 'route'=>'/logout.php','ip'=>$_SERVER['REMOTE_ADDR']??null ], $_SESSION['user_email'], $_SESSION['user_nickname']??null);
}


$_SESSION = array();


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Imposta un tempo nel passato
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}


session_destroy();


header("Location: login.php?loggedout=true"); 
exit;
?>