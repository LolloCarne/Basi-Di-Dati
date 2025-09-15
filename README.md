**Panoramica**
- **Descrizione:** Applicazione didattica di crowdfunding con gestione progetti, reward, componenti (HW), profili (SW), finanziamenti, candidature e commenti. 
Sviluppata dagli studenti Lorenzo Contri e Lorenzo Carnevali.
- **Stack:** PHP, MySQL/MariaDB (mysqli + stored procedure), MongoDB (per logging), Composer per dipendenze.
- **Auth/Ruoli:** Sessioni PHP con ruoli `user`, `creator`, `admin`; helper di permesso in `includes/functions.php`.

**Struttura Cartelle**
- `public/`: Document root dell’applicazione (pagine esposte)
	- `index.php`: home pubblica
	- `login.php`, `logout.php`, `register.php`, `profile.php`: autenticazione e profilo
	- `admin_login.php`, `admin_skill.php`, `skill.php`, `stats.php`: sezioni amministrative/skill/statistiche
	- `projects/`:
		- `list.php`, `detail.php`, `create-view.php`, `search-view.php`: liste, dettaglio, creazione e ricerca progetti
	- `rewards/`:
		- `create.php`, `list_by_project.php`, `modify.php`: gestione reward
- `includes/`: componenti riusabili
	- `functions.php`: bootstrap sessione, costante `BASE_URL`, config include, helper permessi (`is_logged_in`, `check_permission`, `require_login`, `require_permission`), helper DB (`db_conn`, `drain`), `log_to_mongo`
	- `topbar.php`: barra di navigazione
- `config/`: configurazioni
	- `database.php`: costanti DB (`DB_*_MYSQLI`), inizializzazione charset; espone `$mysqli` globale
	- `mongodb.php` (opzionale): stringa di connessione e setup client MongoDB
- `db/`: script SQL (tabelle, procedure, trigger/eventi)
	- `creazioneTabelle.sql`, `creazioneAdminProcedure.sql`, `gestioneProgetti.sql`, `gestioneFinanziamenti.sql`, `gestioneCandidature.sql`, `gestioneCommenti.sql`, `SkillProcedures.sql`, `eventTrigger.sql`, `visualizzazioneStatistiche.sql`
- `uploads/`: contenuti caricati dagli utenti
	- `rewards/`: immagini reward salvate dal server (path pubblici es. `/uploads/rewards/…`)
- `private/`: script non esposti (es. `make_admin.php`)
- `vendor/`: dipendenze Composer (es. `mongodb/mongodb`)
- Root: `composer.json`, `composer.lock`, `vendor/autoload.php`, `phpinfo.php` (diagnostica), file di test/utility

**Flussi Principali**
- **Dettaglio progetto** (`public/projects/detail.php`):
	- Visualizza metadati progetto, progress finanziamenti, reward, componenti (HW), profili (SW), commenti.
	- Azioni: aggiunta/modifica reward, componenti, profili (solo creatore su progetto aperto), finanziamento con reward (utenti, non il creatore), candidature a profili (SW), risposte ai commenti (creatore).
	- Usa stored procedure: `InserisciReward`, `ModificaReward`, `InserisciComponente`, `ModificaComponente`, `InserisciProfilo`, `ModificaProfilo`, `FinanziaProgetto`, `AggiungiCandidatura`, `GestisciCandidatura`, `InserisciCommento`, `InserisciRisposta`.

**Database**
- **Connessione:** costanti in `config/database.php`;
- **Helper:** `db_conn()` crea connessioni dedicate; `drain()` consuma result set multipli dopo `CALL`.
- **Stored Procedure & Trigger:** la logica applicativa è delegata a procedure/trigger negli script `db/` (es. chiusura progetto al raggiungimento budget).

**Logging**
- `includes/functions.php` espone `log_to_mongo(level, message, context, userId, username)`.
- Richiede `config/mongodb.php` e dipendenza `mongodb/mongodb`; salva su DB `bostarter`, collection `Logging`.
