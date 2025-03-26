<?php 

$host = "localhost";
$user = "root";
$password = "";
$database = "bostarter";

$connessione = new mysqli($host, $user, $password, $database);

if($connessione == false){
    die("Error di connesione al DB: ". $connessione->connect_error);
}

$connessione->close();
?>