DELIMITER //


CREATE PROCEDURE `PromuoviUtenteAdAdmin` (
    IN `userEmail` VARCHAR(255),         
    IN `securityCodeHash` VARCHAR(255)  
)
BEGIN
    DECLARE userExists INT DEFAULT 0;

   
    SELECT COUNT(*)
    INTO userExists
    FROM Utente
    WHERE email = userEmail;

    
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