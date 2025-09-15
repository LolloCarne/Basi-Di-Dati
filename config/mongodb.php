<?php
/**
 * Configurazione connessione MongoDB
 *
 * Questo file espone le variabili globali:
 *  - $MONGO_CONNECTION_STRING
 *  - $mongoClient (istanza di MongoDB\Client oppure null se non disponibile)
 *  - $mongoDb (istanza di MongoDB\Database selezionata oppure null)
 *
 * Requisiti:
 *  - Estensione PHP per MongoDB installata (driver + library composer mongodb/mongodb)
 *  - Presenza di vendor/autoload.php già caricata prima di includere questo file (functions.php lo fa tramite require config, ma assicurarsi che l'autoload sia stato richiesto altrove, ad es. in entry scripts)
 *
 * Sicurezza:
 *  Non inserire la password in chiaro nel codice sorgente. Usare variabili d'ambiente o file esterni non versionati (.env).
 */

use MongoDB\Client;

// Evita redefinizione se già incluso
if (isset($mongoClient) && $mongoClient instanceof Client) {
    return; // Già configurato
}

$MONGO_CONNECTION_STRING = null;
$mongoClient = null;
$mongoDb = null; // Impostato dopo la connessione

// 1. Recupero URI da variabile d'ambiente (preferita)
$envUri = getenv('MONGODB_URI');
if ($envUri && trim($envUri) !== '') {
    $MONGO_CONNECTION_STRING = trim($envUri);
}

// 2. (Facoltativo) Recupero da costante definita altrove
if ($MONGO_CONNECTION_STRING === null && defined('MONGODB_URI')) {
    $val = constant('MONGODB_URI');
    if (is_string($val) && trim($val) !== '') {
        $MONGO_CONNECTION_STRING = trim($val);
    }
}

// 3. Fallback: usare una stringa di esempio (NON usare in produzione, richiede sostituzione password)
if ($MONGO_CONNECTION_STRING === null) {
    // Sostituire <db_password> con la password reale in una variabile d'ambiente.
    $MONGO_CONNECTION_STRING = 'mongodb+srv://admin_db_user:o5zJ1o5MSy6tzaFA@cluster0.vxsqfj3.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0';
}

// 4. Validazione di base
if (!preg_match('/^mongodb(\+srv)?:\/\//i', $MONGO_CONNECTION_STRING)) {
    error_log('mongodb.php: Stringa di connessione MongoDB non valida.');
    return; // Non tentare connessione
}

// 5. Verifica disponibilità classe Client
if (!class_exists(Client::class)) {
    error_log('mongodb.php: Libreria MongoDB non disponibile. Assicurarsi di avere run: composer require mongodb/mongodb');
    return;
}

try {
    // Stable API (opzionale)
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

    // 6. Ping per verificare la connessione (try/catch separato per non bloccare il resto in produzione)
    try {
        $mongoClient->selectDatabase('admin')->command(['ping' => 1]);
    } catch (\Throwable $e) {
        error_log('mongodb.php: Ping fallito - ' . $e->getMessage());
    }

    // 7. Seleziona database principale dell'app (personalizzabile via env MONGODB_DB, default "bostarter")
    $targetDb = getenv('MONGODB_DB');
    if (!$targetDb) {
        $targetDb = 'bostarter';
    }
    $mongoDb = $mongoClient->selectDatabase($targetDb);

} catch (\Throwable $e) {
    error_log('mongodb.php: Errore connessione MongoDB - ' . $e->getMessage());
    // Manteniamo le variabili a null così il logging farà fallback su file.
    $mongoClient = null;
    $mongoDb = null;
}
