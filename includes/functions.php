<?php
define('BASE_URL', '/public/');  

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!class_exists('\\MongoDB\\Client')) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }
}


require_once __DIR__ . '/../config/database.php';
if (file_exists(__DIR__ . '/../config/mongodb.php')) {
    require_once __DIR__ . '/../config/mongodb.php';
}

/**
 * Verifica se l'utente è attualmente loggato.
 * @return bool True se l'utente è loggato, False altrimenti.
 */
function is_logged_in(): bool {
    // Usa l'email come identificativo primario nella sessione
    return isset($_SESSION['user_email']);
}

/**
 * Verifica se l'utente loggato ha un ruolo specifico.
 * Considera il flag 'is_admin_verified' per il ruolo 'admin'.
 * @param string|array $required_role Il ruolo richiesto (es. 'admin', 'creator', 'user') o un array.
 * @param bool $require_admin_verified Se true e il ruolo richiesto è 'admin',
 * verifica anche $_SESSION['is_admin_verified'].
 * @return bool True se l'utente ha il permesso, False altrimenti.
 */
function check_permission($required_role, bool $require_admin_verified = true): bool {
    if (!is_logged_in()) {
        return false;
    }

    $user_role = $_SESSION['user_ruolo'] ?? null;

    if ($user_role === null) {
        return false;
    }

    $roles_to_check = is_array($required_role) ? $required_role : [$required_role];


    foreach ($roles_to_check as $role) {
        if ($user_role === $role) {
             if ($role === 'admin' && $require_admin_verified) {
                 return isset($_SESSION['is_admin_verified']) && $_SESSION['is_admin_verified'] === true;
             }
             return true;
        }
    }

     if (in_array('creator', $roles_to_check) && $user_role === 'admin' && isset($_SESSION['is_admin_verified']) && $_SESSION['is_admin_verified'] === true) {
        return true;
     }
      if (in_array('user', $roles_to_check) && $user_role === 'admin' && isset($_SESSION['is_admin_verified']) && $_SESSION['is_admin_verified'] === true) {
        return true;
     }
     if (in_array('user', $roles_to_check) && $user_role === 'creator') {
         return true;
     }


    return false; 
}


/**
 * Forza il login. Reindirizza se non loggato.
 * @param string $redirect_to
 */
function require_login(string $redirect_to = 'login.php'): void {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . $redirect_to);
        exit;
    }
}

/**
 * Forza un permesso specifico.Motivo: token expired or invalid: 403
 
 
 * @param string|array $role Ruolo/i richiesti.
 * @param bool $require_admin_verified Richiedi verifica codice per admin.
 * @param string $fail_action 'die' o 'redirect'.
 * @param string $redirect_url URL per redirect.
 */
function require_permission(
    $role,
    bool $require_admin_verified = true,
    string $fail_action = 'die',
    string $redirect_url = 'index.php'
): void {
    // Passa il flag require_admin_verified a check_permission
    if (!check_permission($role, $require_admin_verified)) {
        if ($fail_action === 'redirect') {
            header("Location: " . $redirect_url . "?error=permission_denied");
            exit;
        } else {
            http_response_code(403);
            die("Accesso Negato. Non hai i permessi necessari (Ruolo richiesto: " . (is_array($role) ? implode('/', $role) : $role) . ").");
        }
    }
}


/**
 * Inserisce un documento di log in MongoDB nella collection `bostarter.Logging`.
 * Restituisce true se l'inserimento ha avuto successo, false altrimenti.
 * @param string $level Livello di log (INFO, WARN, ERROR, DEBUG)
 * @param string $message Messaggio del log
 * @param array $context Dati aggiuntivi opzionali (ip, route, method, ecc.)
 * @param string|null $userId Id dell'utente (o null se anonimo)
 * @param string|null $username Username dell'utente (opzionale)
 * @return bool
 */
function log_to_mongo(string $level, string $message, array $context = [], $userId = null, $username = null): bool {
    try {
        global $mongoClient, $MONGO_CONNECTION_STRING, $mongoDb;

        $fallbackLogFile = __DIR__ . '/../logs/mongo_errors.log';
        if (!is_dir(dirname($fallbackLogFile))) {
            @mkdir(dirname($fallbackLogFile), 0755, true);
        }

        if (!class_exists('\MongoDB\\Client')) {
            $msg = '[' . date('c') . '] log_to_mongo: MongoDB\\Client non disponibile. Saltato il log.' . PHP_EOL;
            error_log($msg);
            @file_put_contents($fallbackLogFile, $msg, FILE_APPEND | LOCK_EX);
            return false;
        }

        if ($mongoClient === null) {
            if (!empty($MONGO_CONNECTION_STRING)) {
                try {
                    $mongoClient = new \MongoDB\Client($MONGO_CONNECTION_STRING);
                } catch (\Throwable $e) {
                    $msg = '[' . date('c') . '] log_to_mongo: Impossibile creare MongoDB\\Client: ' . $e->getMessage() . PHP_EOL;
                    error_log($msg);
                    @file_put_contents($fallbackLogFile, $msg, FILE_APPEND | LOCK_EX);
                    return false;
                }
            } else {
                $msg = '[' . date('c') . '] log_to_mongo: Nessuna connessione MongoDB configurata.' . PHP_EOL;
                error_log($msg);
                @file_put_contents($fallbackLogFile, $msg, FILE_APPEND | LOCK_EX);
                return false;
            }
        }

        $db = $mongoDb ?? $mongoClient->selectDatabase('bostarter');
        $collection = $db->selectCollection('Logging');

        $utcClass = '\\MongoDB\\BSON\\UTCDateTime';
        if (class_exists($utcClass)) {
            $timestamp = new $utcClass((int)(microtime(true) * 1000));
        } else {
            $dt = new \DateTime('now', new \DateTimeZone('UTC'));
            $timestamp = $dt;
        }

        $doc = [
            'user' => [
                'id' => $userId !== null ? (string)$userId : null,
                'username' => $username !== null ? (string)$username : null
            ],
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message
        ];

        if (!empty($context)) {
            $doc['context'] = $context;
        }

        $collection->insertOne($doc);
        return true;
    } catch (\Throwable $e) {
        $err = '[' . date('c') . '] log_to_mongo error: ' . $e->getMessage() . '\nDoc: ' . json_encode(isset($doc) ? $doc : []) . PHP_EOL;
        error_log($err);
        @file_put_contents($fallbackLogFile, $err, FILE_APPEND | LOCK_EX);
        return false;
    }
}

?>