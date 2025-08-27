DELIMITER $$

CREATE PROCEDURE InserisciCommento(
    IN p_email_utente VARCHAR(255),
    IN p_nome_progetto VARCHAR(255),
    IN p_contenuto TEXT
)
BEGIN
    
    IF EXISTS (SELECT 1 FROM Utente WHERE email = p_email_utente)
       AND EXISTS (SELECT 1 FROM Progetto WHERE nome = p_nome_progetto) THEN
       
        INSERT INTO Commento(contenuto, id_utente, nome_progetto)
        VALUES (p_contenuto, p_email_utente, p_nome_progetto);
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Utente o progetto inesistente';
    END IF;
END$$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE InserisciRisposta(
    IN p_email_creatore VARCHAR(255),
    IN p_id_commento INT,
    IN p_testo TEXT
)
BEGIN
    DECLARE v_nome_progetto VARCHAR(255);

    
    SELECT nome_progetto
    INTO v_nome_progetto
    FROM Commento
    WHERE id = p_id_commento;

   
    IF v_nome_progetto IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Commento inesistente';
    END IF;

    
    IF EXISTS (
        SELECT 1
        FROM Progetto
        WHERE nome = v_nome_progetto
          AND creatore_email = p_email_creatore
    ) THEN
        INSERT INTO Risposta(email_creatore, id_commento, testo)
        VALUES (p_email_creatore, p_id_commento, p_testo);
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Il creatore non è autorizzato a rispondere a questo commento';
    END IF;
END$$

DELIMITER ;
