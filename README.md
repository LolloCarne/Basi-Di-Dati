# BOSTARTER - Progetto Basi di Dati

Questo repository contiene l'implementazione (MySQL + PHP) della piattaforma di crowdfunding BOSTARTER.

## Stato Implementazione Frontend (agg. 2025-08-28)

Funzionalità coperte mediante stored procedure:

| Ambito | Operazioni | Stored Procedure Richiamate |
|--------|------------|-----------------------------|
| Progetti | Inserimento, dettaglio, ricerca | `InserisciProgetto`, `DettagliProgetto`, `ricercaProgetti` |
| Reward | Inserisci / Modifica | `InserisciReward`, `ModificaReward` |
| Componenti (HW) | Inserisci / Modifica | `InserisciComponente`, `ModificaComponente` |
| Profili (SW) | Inserisci / Modifica | `InserisciProfilo`, `ModificaProfilo` |
| Finanziamenti | Inserimento + trigger chiusura | `FinanziaProgetto` |
| Skill utente | CRUD | `GetUtenteSkill`, `AddUpdateUtenteSkill`, `DeleteUtenteSkill` |
| Commenti | Inserisci / Risposta | `InserisciCommento`, `InserisciRisposta` |
| Candidature | Inserisci / Gestisci | `AggiungiCandidatura`, `GestisciCandidatura` |
| Statistiche | 3 classifiche | `CreatoriAffidabili`, `ProgettiViciniBudget`, `TopUtentiFinanziatori` |

## Nuove Pagine

- `public/index.php`: Home autenticata con navigazione.
- `public/projects/detail.php`: Esteso con commenti, candidature e relativa gestione.
- `public/stats.php`: Visualizzazione classifiche/statistiche.
- `public/admin_skill.php`: Inserimento nuove competenze (solo admin verificato).

## Requisiti
1. Importare tutte le tabelle e le stored procedure contenute nella cartella `db/` nell'istanza MySQL.
2. Configurare credenziali in `config/database.php`.
3. Creare almeno un utente admin/creator per test.

## Avvio
Collegare il root di `public/` al web server (es. Apache `DocumentRoot` o integrazione con PHP built-in) e accedere a `login.php`.

## TODO / Miglioramenti
- UI/UX con Bootstrap.
- Logging eventi su MongoDB (richiesto dalla traccia, non ancora implementato qui).
- Validazione lato client e messaggi di errore più user-friendly.
- Hardening sicurezza (CSRF token, rate limiting login, prepared statement ovunque - già usati dove necessario).

## Uso di MongoDB
Questo progetto ora include una configurazione opzionale per connettersi a un cluster MongoDB Atlas.

- **Installazione estensione PHP**: su Windows installa l'estensione `php_mongodb.dll` compatibile con la tua versione di PHP. Scaricala da PECL (https://pecl.php.net/package/mongodb) oppure usa il pacchetto di PHP manager che stai usando (XAMPP/WAMP). Dopo aver copiato la DLL, aggiungi `extension=mongodb` in `php.ini` e riavvia il webserver.
- **Composer**: il wrapper consigliato è la libreria ufficiale `mongodb/mongodb`. Se non hai Composer, installalo seguendo https://getcomposer.org/download/. Poi esegui nella root del progetto:

```powershell
composer require mongodb/mongodb
```

- **Configurazione**: la stringa di connessione può essere modificata in `config/mongodb.php`. Di default il progetto cerca il file e carica un client MongoDB come `$mongoClient`.
- **Esempio d'uso**: nel codice PHP puoi usare il client importato automaticamente includendo `includes/functions.php` in cima alla pagina. Esempio:

```php
// Assumendo che includes/functions.php sia incluso
global $mongoClient; // disponibile se l'estensione e composer sono installati
$db = $mongoClient->selectDatabase('bostarter');
$collection = $db->selectCollection('logs');
$collection->insertOne(['event' => 'login', 'user' => 'email@example.com', 'ts' => new MongoDB\BSON\UTCDateTime()]);
```

Se `$mongoClient` è `null`, significa che la libreria/estensione non è presente o la connessione non è riuscita. Controlla i log PHP e il file `config/mongodb.php`.

---
Per dubbi o estensioni aprire una issue o aggiungere una sezione al README.

## Logging Avanzato MongoDB

Il progetto integra ora un logger avanzato per gli eventi principali del driver MongoDB (CommandStarted, CommandSucceeded, CommandFailed).

- **File logger**: `logs/mongodb_events.log`
- **Codice**: `includes/mongo_logger.php` (subscriber e helper `mongo_log`)
- **Attivazione**: il subscriber viene importato automaticamente da `config/mongodb.php` se le API di monitoring sono disponibili.

Come testare rapidamente:

1. Verifica che l'estensione PHP `mongodb` e `mongodb/mongodb` siano installate.
2. Apri il browser su `public/test_mongo_logging.php` oppure esegui da CLI:

```powershell
php public/test_mongo_logging.php
```

3. Controlla il file `logs/mongodb_events.log` per vedere gli eventi registrati.

Note:
- In ambienti shared o di produzione potresti voler integrare un rotator di log più robusto o inviare gli eventi a un servizio esterno (Graylog/ELK/Datadog).
- Se le funzioni di monitoring non sono disponibili, il codice non registra eventi avanzati ma mantiene la connettività al client MongoDB.
