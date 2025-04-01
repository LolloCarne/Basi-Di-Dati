<?php
// config/database.php

define('DB_HOST_MYSQLI', 'localhost');       
define('DB_USER_MYSQLI', 'root');
define('DB_PASS_MYSQLI', '');
define('DB_NAME_MYSQLI', 'bostarter');
define('DB_PORT_MYSQLI', 3306);             
define('DB_CHARSET_MYSQLI', 'utf8mb4');

// Crea la connessione mysqli
$mysqli = new mysqli(DB_HOST_MYSQLI, DB_USER_MYSQLI, DB_PASS_MYSQLI, DB_NAME_MYSQLI, DB_PORT_MYSQLI);

// Controlla errori di connessione
if ($mysqli->connect_error) {
    // In produzione, logga l'errore e mostra messaggio generico
    error_log("Errore Connessione DB (mysqli): " . $mysqli->connect_error);
    die("Errore di connessione al database. Si prega di riprovare più tardi.");
}

// Imposta il set di caratteri (importante per dati multilingua/speciali)
if (!$mysqli->set_charset(DB_CHARSET_MYSQLI)) {
    error_log("Errore nel caricamento del set di caratteri utf8mb4 (mysqli): " . $mysqli->error);
}

?>