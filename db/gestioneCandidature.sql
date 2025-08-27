DELIMITER $$

CREATE PROCEDURE AggiungiCandidatura(
    IN p_email_utente VARCHAR(255),
    IN p_nome_progetto VARCHAR(255),
    IN p_nome_profilo VARCHAR(255)
)
BEGIN
    DECLARE v_tipo_progetto ENUM('hardware','software');
    DECLARE v_competenza VARCHAR(50);
    DECLARE v_livello_richiesto INT;
    DECLARE v_livello_utente INT;

    
    SELECT tipo_progetto
    INTO v_tipo_progetto
    FROM Progetto
    WHERE nome = p_nome_progetto;

    IF v_tipo_progetto IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Progetto inesistente';
    END IF;

    IF v_tipo_progetto <> 'software' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La candidatura è permessa solo per progetti software';
    END IF;

    
    IF NOT EXISTS (SELECT 1 FROM Utente WHERE email = p_email_utente) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Utente inesistente';
    END IF;

    
    SELECT competenza, livello
    INTO v_competenza, v_livello_richiesto
    FROM ProfiloCompetenze
    WHERE nome = p_nome_profilo;

    IF v_competenza IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Profilo richiesto inesistente';
    END IF;

    
    SELECT livello
    INTO v_livello_utente
    FROM UtenteSkill us
    JOIN Skill s ON us.competenza = s.id
    WHERE us.utente_email = p_email_utente
      AND s.competenza = v_competenza;

    IF v_livello_utente IS NULL OR v_livello_utente < v_livello_richiesto THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le skill dell’utente non soddisfano i requisiti del profilo';
    END IF;

    
    IF NOT EXISTS (
        SELECT 1 FROM Candidatura
        WHERE email_utente = p_email_utente
          AND nome_progetto = p_nome_progetto
          AND nome_profilo = p_nome_profilo
    ) THEN
        INSERT INTO Candidatura(email_utente, nome_profilo, nome_progetto)
        VALUES (p_email_utente, p_nome_profilo, p_nome_progetto);
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Candidatura già presente';
    END IF;

END$$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE GestisciCandidatura(
    IN p_email_creatore VARCHAR(255),
    IN p_id_candidatura INT,
    IN p_stato ENUM('accettata','rifiutata')
)
BEGIN
    DECLARE v_nome_progetto VARCHAR(255);
    DECLARE v_tipo_progetto ENUM('hardware','software');

    
    SELECT nome_progetto
    INTO v_nome_progetto
    FROM Candidatura
    WHERE id = p_id_candidatura;

    IF v_nome_progetto IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Candidatura inesistente';
    END IF;

    
    SELECT tipo_progetto
    INTO v_tipo_progetto
    FROM Progetto
    WHERE nome = v_nome_progetto
      AND creatore_email = p_email_creatore;

    IF v_tipo_progetto IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Non sei autorizzato a gestire questa candidatura';
    END IF;

    IF v_tipo_progetto <> 'software' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La gestione candidature è permessa solo per progetti software';
    END IF;

    
    UPDATE Candidatura
    SET stato = p_stato
    WHERE id = p_id_candidatura;
END$$

DELIMITER ;
