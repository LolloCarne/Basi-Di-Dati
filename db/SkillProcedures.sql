DELIMITER $$

CREATE PROCEDURE GetUtenteSkill(IN p_email VARCHAR(255))
BEGIN
    SELECT 
        us.competenza AS skill_id, 
        s.competenza AS skill_name, 
        us.livello 
    FROM UtenteSkill us
    JOIN Skill s ON us.competenza = s.id
    WHERE us.utente_email = p_email;
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE AddUpdateUtenteSkill(
    IN p_email VARCHAR(255),
    IN p_skill_id INT,
    IN p_livello INT
)
BEGIN
    IF EXISTS (SELECT 1 FROM UtenteSkill WHERE utente_email = p_email AND competenza = p_skill_id) THEN
        UPDATE UtenteSkill 
        SET livello = p_livello 
        WHERE utente_email = p_email AND competenza = p_skill_id;
    ELSE
        INSERT INTO UtenteSkill (utente_email, competenza, livello)
        VALUES (p_email, p_skill_id, p_livello);
    END IF;
END $$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE DeleteUtenteSkill(
    IN p_email VARCHAR(255),
    IN p_skill_id INT
)
BEGIN
    DELETE FROM UtenteSkill 
    WHERE utente_email = p_email AND competenza = p_skill_id;
END $$

DELIMITER ;
