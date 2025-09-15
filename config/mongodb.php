<?php
use MongoDB\Client;

if (isset($mongoClient) && $mongoClient instanceof Client) {
    return; // Già configurato
}

$MONGO_CONNECTION_STRING = null;
$mongoClient = null;
$mongoDb = null; 

$envUri = getenv('MONGODB_URI');
if ($envUri && trim($envUri) !== '') {
    $MONGO_CONNECTION_STRING = trim($envUri);
}

if ($MONGO_CONNECTION_STRING === null && defined('MONGODB_URI')) {
    $val = constant('MONGODB_URI');
    if (is_string($val) && trim($val) !== '') {
        $MONGO_CONNECTION_STRING = trim($val);
    }
}

if ($MONGO_CONNECTION_STRING === null) {
    $MONGO_CONNECTION_STRING = 'mongodb+srv://admin_db_user:o5zJ1o5MSy6tzaFA@cluster0.vxsqfj3.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0';
}

if (!preg_match('/^mongodb(\+srv)?:\/\//i', $MONGO_CONNECTION_STRING)) {
    error_log('mongodb.php: Stringa di connessione MongoDB non valida.');
    return; // Non valida
}

if (!class_exists(Client::class)) {
    error_log('mongodb.php: Libreria MongoDB non disponibile. Assicurarsi di avere run: composer require mongodb/mongodb');
    return;
}

try {
    $driverOptions = [];
    if (class_exists('MongoDB\\Driver\\ServerApi')) {
        try {
            $serverApiClass = 'MongoDB\\Driver\\ServerApi';
            $serverApi = new $serverApiClass(constant($serverApiClass . '::V1'));
            $driverOptions['serverApi'] = $serverApi;
        } catch (\Throwable $e) {
            error_log('mongodb.php: Impossibile inizializzare ServerApi - ' . $e->getMessage());
        }
    }

    $mongoClient = new Client($MONGO_CONNECTION_STRING, [], $driverOptions);

    try {
        $mongoClient->selectDatabase('admin')->command(['ping' => 1]);
    } catch (\Throwable $e) {
        error_log('mongodb.php: Ping fallito - ' . $e->getMessage());
    }

    $targetDb = getenv('MONGODB_DB');
    if (!$targetDb) {
        $targetDb = 'bostarter';
    }
    $mongoDb = $mongoClient->selectDatabase($targetDb);

} catch (\Throwable $e) {
    error_log('mongodb.php: Errore connessione MongoDB - ' . $e->getMessage());
    $mongoClient = null;
    $mongoDb = null;
}
