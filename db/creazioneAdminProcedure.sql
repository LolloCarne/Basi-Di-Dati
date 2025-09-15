DELIMITER //


CREATE PROCEDURE `PromuoviUtenteAdAdmin` (
    IN `userEmail` VARCHAR(255),         
    IN `securityCodeHash` VARCHAR(255)  
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
            codice_sicurezza = securityCodeHash 
        WHERE
            email = userEmail;
    ELSE
        SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Errore: Utente non trovato con la mail specificata.';
    END IF;

END //


DELIMITER ;