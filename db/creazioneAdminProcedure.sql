DELIMITER //

-- Crea la procedura
CREATE PROCEDURE `PromuoviUtenteAdAdmin` (
    IN `userEmail` VARCHAR(255),         -- Input: Email dell'utente da promuovere
    IN `securityCodeHash` VARCHAR(255)  -- Input: Codice di sicurezza GIA' HASHATO da PHP
)
BEGIN
    DECLARE userExists INT DEFAULT 0;

    -- 1. Controlla se l'utente con quella email esiste nella tabella Utente
    SELECT COUNT(*)
    INTO userExists
    FROM Utente
    WHERE email = userEmail;

    -- 2. Se l'utente esiste (COUNT(*) > 0), aggiorna il suo codice_sicurezza
    IF userExists > 0 THEN
        UPDATE Utente
        SET
            codice_sicurezza = securityCodeHash -- Imposta l'hash ricevuto
        WHERE
            email = userEmail;
    ELSE
        SIGNAL SQLSTATE '45000' -- Codice di errore generico per errori definiti dall'utente
            SET MESSAGE_TEXT = 'Errore: Utente non trovato con la mail specificata.';
    END IF;

END //

-- Ripristina il delimitatore standard
DELIMITER ;