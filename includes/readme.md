## File functions spiegazione

is_logged_in(): Ora controlla $_SESSION['user_email'].
check_permission():
Accetta un parametro $require_admin_verified (default true).
Quando controlla il ruolo admin, se $require_admin_verified è true, verifica anche $_SESSION['is_admin_verified']. Questo assicura che per azioni sensibili, l'admin abbia usato admin_login.php.
Implementa una gerarchia: un admin verificato può fare azioni da 'creator' e 'user'; un 'creator' può fare azioni da 'user'.
require_permission(): Passa il flag $require_admin_verified alla funzione check_permission.
Come usarlo:

Pagina profilo (tutti gli utenti loggati): require_login();
Pagina crea progetto (solo creatori o admin verificati): require_permission('creator'); (userà il default require_admin_verified = true)
Pagina gestione utenti (solo admin verificato): require_permission('admin'); (userà il default require_admin_verified = true)
Pagina con statistiche visibili a tutti gli utenti loggati: require_permission('user'); (anche creator e admin verificati passeranno questo controllo).
Ricorda:

Hashing: Applica password_hash() quando registri un utente o imposti/modifichi la password o il codice di sicurezza.
mysqli Error Handling: Controlla sempre il valore restituito da $mysqli->prepare() e $stmt->execute(). Logga gli errori con error_log() e $mysqli->error o $stmt->error.
Chiusura Connessione/Statement: Chiudi gli statement ($stmt->close()) quando hai finito. La connessione $mysqli può essere chiusa alla fine dello script principale o gestita diversamente se necessario.