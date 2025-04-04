DELIMITER $$

CREATE PROCEDURE InserisciProgetto(
    IN p_nome VARCHAR(255),
    IN p_creatore_email VARCHAR(255),
    IN p_descrizione TEXT,
    IN p_budget DECIMAL(15,2),
    IN p_data_limite DATE,
    IN p_stato ENUM('aperto', 'chiuso'),
    IN p_tipo_progetto ENUM('hardware', 'software')
)
BEGIN
    DECLARE creatore_esiste INT;
    
    -- Controlla se l'utente è un creatore
    SELECT COUNT(*) INTO creatore_esiste FROM Creatore WHERE utente_email = p_creatore_email;
    
    IF creatore_esiste = 1 THEN
        INSERT INTO Progetto (nome, creatore_email, descrizione, data_inserimento, budget, data_limite, stato, tipo_progetto)
        VALUES (p_nome, p_creatore_email, p_descrizione, CURDATE(), p_budget, p_data_limite, p_stato, p_tipo_progetto);
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore: solo i creatori possono inserire progetti';
    END IF;
END $$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE VisualizzaProgetti()
BEGIN
    SELECT nome FROM Progetto;
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE DettagliProgetto(
    IN p_nome VARCHAR(255)
)
BEGIN
   SELECT * FROM Progetto WHERE nome = p_nome;
END $$

DELIMITER ;

DELIMITER $$

DELIMITER $$

CREATE PROCEDURE InserisciReward(
    IN r_codice VARCHAR(50),
    IN r_descrizione TEXT,
    IN r_foto VARCHAR(255),
    IN p_creatore_email VARCHAR(255),
    IN p_nome_progetto VARCHAR(255)
)
BEGIN
    DECLARE creatore_esiste INT;
    DECLARE progetto_creatore_email VARCHAR(255);
    DECLARE progetto_esiste INT;
    
    -- Controlla se il creatore esiste
    SELECT COUNT(*) INTO creatore_esiste 
    FROM Creatore 
    WHERE utente_email = p_creatore_email;
    
    -- Se il creatore esiste, controlla se il progetto esiste e se è stato creato da lui
    IF creatore_esiste = 1 THEN
        -- Verifica se il progetto esiste e se l'utente è il creatore del progetto
        SELECT creatore_email INTO progetto_creatore_email
        FROM Progetto 
        WHERE nome = p_nome_progetto;
        
        -- Verifica che il creatore del progetto sia lo stesso che sta cercando di inserire il reward
        SELECT COUNT(*) INTO progetto_esiste 
        FROM Progetto 
        WHERE nome = p_nome_progetto AND creatore_email = p_creatore_email;
        
        -- Se il progetto esiste e il creatore è lo stesso, inserisci la reward
        IF progetto_esiste = 1 THEN
            -- Inserisce il reward
            INSERT INTO Reward (codice, descrizione, foto)
            VALUES (r_codice, r_descrizione, r_foto);
            
            -- Associa la reward al progetto
            INSERT INTO RewardProgetto (id_progetto, codice_reward)
            VALUES (p_nome_progetto, r_codice);
        ELSE
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Errore: il progetto non esiste o non sei il creatore del progetto';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore: solo i creatori possono inserire rewards';
    END IF;
END $$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE ModificaReward(
    IN p_codice VARCHAR(50),
    IN p_descrizione TEXT,
    IN p_foto VARCHAR(255),
    IN p_creatore_email VARCHAR(255)
)
BEGIN
    DECLARE reward_esiste INT;
    DECLARE creatore_esiste INT;
    DECLARE progetto_creatore_email VARCHAR(255);
    
    -- Controlla se il reward esiste
    SELECT COUNT(*) INTO reward_esiste 
    FROM Reward 
    WHERE codice = p_codice;
    
    -- Se il reward esiste, recupera l'email del creatore del progetto associato
    IF reward_esiste = 1 THEN
        -- Recupera l'email del creatore del progetto a cui il reward è associato
        SELECT p.creatore_email INTO progetto_creatore_email
        FROM Reward r
        JOIN RewardProgetto rp ON r.codice = rp.codice_reward
        JOIN Progetto p ON rp.id_progetto = p.nome
        WHERE r.codice = p_codice;
        
        -- Controlla se l'utente che vuole fare la modifica è il creatore del progetto
        IF progetto_creatore_email = p_creatore_email THEN
            -- Aggiorna la reward
            UPDATE Reward 
            SET descrizione = p_descrizione, foto = p_foto
            WHERE codice = p_codice;
        ELSE
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Errore: non sei il creatore del progetto associato al reward';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Errore: il reward specificato non esiste';
    END IF;
END $$

DELIMITER


DELIMITER $$

CREATE PROCEDURE InserisciComponente(
    IN p_nome_componente VARCHAR(255),
    IN p_descrizione TEXT,
    IN p_prezzzo DECIMAL(15,2),
    IN p_quantita INT,
    IN p_creatore_email VARCHAR(255),
    IN p_nome_progetto VARCHAR(255)
)
BEGIN
    DECLARE progetto_esiste INT;
    DECLARE progetto_tipo ENUM('hardware', 'software');
    DECLARE progetto_creatore_email VARCHAR(255);
    DECLARE creatore_esiste INT;
    
    -- Controlla se il creatore esiste
    SELECT COUNT(*) INTO creatore_esiste 
    FROM Creatore 
    WHERE utente_email = p_creatore_email;

    -- Se il creatore esiste, controlla il progetto
    IF creatore_esiste = 1 THEN
        -- Verifica se il progetto esiste e se il tipo di progetto è "hardware"
        SELECT tipo_progetto, creatore_email INTO progetto_tipo, progetto_creatore_email
        FROM Progetto 
        WHERE nome = p_nome_progetto;
        
        -- Verifica che il progetto esista e che il tipo sia "hardware"
        IF projeto_tipo = 'hardware' AND progetto_creatore_email = p_creatore_email THEN
            -- Inserisce la componente nel progetto
            INSERT INTO Componenti (nome, descrizione, prezzo, quantità)
            VALUES (p_nome_componente, p_descrizione, p_prezzzo, p_quantita);
            
            -- Associa la componente al progetto
            INSERT INTO ComponentiProgetto (nome_progetto, nome_componenti)
            VALUES (p_nome_progetto, p_nome_componente);
        ELSE
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Errore: il progetto deve essere di tipo hardware e l utente deve essere il creatore del progetto';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Errore: solo i creatori del progetto possono inserire componenti';
    END IF;
END $$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE ModificaComponente(
    IN p_nome_componente VARCHAR(255),
    IN p_descrizione TEXT,
    IN p_prezzzo DECIMAL(15,2),
    IN p_quantita INT,
    IN p_creatore_email VARCHAR(255),
    IN p_nome_progetto VARCHAR(255)
)
BEGIN
    DECLARE progetto_esiste INT;
    DECLARE progetto_tipo ENUM('hardware', 'software');
    DECLARE progetto_creatore_email VARCHAR(255);
    DECLARE creatore_esiste INT;

    -- Controlla se il creatore esiste
    SELECT COUNT(*) INTO creatore_esiste 
    FROM Creatore 
    WHERE utente_email = p_creatore_email;

    -- Se il creatore esiste, controlla il progetto
    IF creatore_esiste = 1 THEN
        -- Verifica se il progetto esiste e se il tipo di progetto è "hardware"
        SELECT tipo_progetto, creatore_email INTO progetto_tipo, progetto_creatore_email
        FROM Progetto 
        WHERE nome = p_nome_progetto;

        -- Verifica che il progetto esista e che il tipo sia "hardware"
        IF progetto_tipo = 'hardware' AND progetto_creatore_email = p_creatore_email THEN
            -- Modifica la componente nel progetto
            UPDATE Componenti
            SET descrizione = p_descrizione, prezzo = p_prezzzo, quantità = p_quantita
            WHERE nome = p_nome_componente;
            
            -- Verifica se la componente esiste nel progetto 
            IF ROW_COUNT() = 0 THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'Errore: la componente non esiste nel progetto';
            END IF;

        ELSE
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Errore: il progetto deve essere di tipo hardware e l utente deve essere il creatore del progetto';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Errore: solo i creatori del progetto possono modificare le componenti';
    END IF;
END $$

DELIMITER ;



DELIMITER $$

CREATE PROCEDURE InserisciProfilo(
    IN p_nome_profilo VARCHAR(255),
    IN p_competenze VARCHAR(50),
    IN p_livello INT,
    IN p_creatore_email VARCHAR(255),
    IN p_nome_progetto VARCHAR(255)
)
BEGIN
    DECLARE progetto_esiste INT;
    DECLARE progetto_tipo ENUM('hardware', 'software');
    DECLARE progetto_creatore_email VARCHAR(255);
    DECLARE creatore_esiste INT;

    -- Controlla se il creatore esiste
    SELECT COUNT(*) INTO creatore_esiste 
    FROM Creatore 
    WHERE utente_email = p_creatore_email;

    -- Se il creatore esiste, controlla il progetto
    IF creatore_esiste = 1 THEN
        -- Verifica se il progetto esiste e se il tipo di progetto è "software"
        SELECT tipo_progetto, creatore_email INTO progetto_tipo, progetto_creatore_email
        FROM Progetto 
        WHERE nome = p_nome_progetto;

        -- Verifica che il progetto esista e che il tipo sia "software"
        IF progetto_tipo = 'software' AND progetto_creatore_email = p_creatore_email THEN
            -- Inserisce il profilo nel progetto (profili vengono associati tramite la tabella ProfiloCompetenze)
            INSERT INTO ProfiloCompetenze (nome, competenza, livello)
            VALUES (p_nome_profilo, p_competenze, p_livello);
            
            -- Verifica se il profilo esiste già nel progetto
            -- Se il profilo esiste già, l'inserimento non avviene.
            INSERT INTO Candidatura (email_utente, nome_profilo, nome_progetto)
            VALUES (p_creatore_email, p_nome_profilo, p_nome_progetto);
        ELSE
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Errore: il progetto deve essere di tipo software e l utente deve essere il creatore del progetto';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Errore: solo i creatori del progetto possono inserire i profili';
    END IF;
END $$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE ModificaProfilo(
    IN p_nome_profilo VARCHAR(255),
    IN p_competenze VARCHAR(50),
    IN p_livello INT,
    IN p_creatore_email VARCHAR(255),
    IN p_nome_progetto VARCHAR(255)
)
BEGIN
    DECLARE progetto_esiste INT;
    DECLARE progetto_tipo ENUM('hardware', 'software');
    DECLARE progetto_creatore_email VARCHAR(255);
    DECLARE creatore_esiste INT;
    
    -- Controlla se il creatore esiste
    SELECT COUNT(*) INTO creatore_esiste 
    FROM Creatore 
    WHERE utente_email = p_creatore_email;

    -- Se il creatore esiste, controlla il progetto
    IF creatore_esiste = 1 THEN
        -- Verifica se il progetto esiste e se il tipo di progetto è "software"
        SELECT tipo_progetto, creatore_email INTO progetto_tipo, progetto_creatore_email
        FROM Progetto 
        WHERE nome = p_nome_progetto;

        -- Verifica che il progetto esista e che il tipo sia "software"
        IF progetto_tipo = 'software' AND progetto_creatore_email = p_creatore_email THEN
            -- Modifica il profilo nel progetto (aggiornando competenza e livello)
            UPDATE ProfiloCompetenze
            SET competenza = p_competenze, livello = p_livello
            WHERE nome = p_nome_profilo;
            
            -- Verifica se il profilo esiste nel progetto
            IF ROW_COUNT() = 0 THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'Errore: il profilo non esiste nel progetto';
            END IF;

            -- Opzionale: Aggiornare anche la tabella Candidatura se il profilo è già candidato
            UPDATE Candidatura
            SET nome_profilo = p_nome_profilo
            WHERE nome_progetto = p_nome_progetto;
        ELSE
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Errore: il progetto deve essere di tipo software e l utente deve essere il creatore del progetto';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Errore: solo i creatori del progetto possono modificare i profili';
    END IF;
END $$

DELIMITER ;


















