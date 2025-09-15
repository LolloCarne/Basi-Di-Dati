<?php
// Script di test per la funzione log_to_mongo
// Uso: CLI => php test_mongo_log.php
// Oppure via browser: http://localhost/.../test_mongo_log.php

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/functions.php';

$extra = [
    'test' => true,
    'time' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
    'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
];

$ok = log_to_mongo('INFO', 'Test di connessione (manuale)', $extra, null, 'tester');

if ($ok) {
    echo "OK log inserito\n";
} else {
    echo "Log fallito (vedi logs/mongo_errors.log)\n";
}

// Output diagnostico opzionale
if (php_sapi_name() === 'cli') {
    echo "Connessione: " . (isset($MONGO_CONNECTION_STRING) ? '[SET]' : '[MISSING]') . "\n";
    if (isset($mongoClient) && $mongoClient instanceof MongoDB\Client) {
        echo "Client attivo: sì\n";
    } else {
        echo "Client attivo: no\n";
    }
}
