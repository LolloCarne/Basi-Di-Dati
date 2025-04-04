<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

    // L'admin ha accesso a tutto tranne quando è richiesta verifica specifica?
    // O verifichiamo ruolo per ruolo? Andiamo con la seconda opzione, più sicura.

    foreach ($roles_to_check as $role) {
        if ($user_role === $role) {
             // Se il ruolo richiesto è 'admin', controlla anche la verifica
             if ($role === 'admin' && $require_admin_verified) {
                 return isset($_SESSION['is_admin_verified']) && $_SESSION['is_admin_verified'] === true;
             }
             // Per altri ruoli ('creator', 'user'), basta la corrispondenza
             return true;
        }
    }

     // Caso speciale: un admin verificato può fare cose da creatore? Sì.
     if (in_array('creator', $roles_to_check) && $user_role === 'admin' && isset($_SESSION['is_admin_verified']) && $_SESSION['is_admin_verified'] === true) {
        return true;
     }
     // Caso speciale: un admin verificato può fare cose da utente? Sì.
      if (in_array('user', $roles_to_check) && $user_role === 'admin' && isset($_SESSION['is_admin_verified']) && $_SESSION['is_admin_verified'] === true) {
        return true;
     }
     // Caso speciale: un creatore può fare cose da utente? Sì.
     if (in_array('user', $roles_to_check) && $user_role === 'creator') {
         return true;
     }


    return false; // Nessuna corrispondenza trovata
}


/**
 * Forza il login. Reindirizza se non loggato.
 * @param string $redirect_to
 */
function require_login(string $redirect_to = 'login.php'): void {
    if (!is_logged_in()) {
        header("Location: " . $redirect_to);
        exit;
    }
}

/**
 * Forza un permesso specifico.
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

?>