
-- Trigger : Aggiornamento Affidabilità

DELIMITER $$


CREATE TRIGGER aggiorna_affidabilita_progetto
AFTER INSERT ON Progetto
FOR EACH ROW
BEGIN
    UPDATE Creatore
    SET affidabilità = affidabilità + 1
    WHERE utente_email = NEW.creatore_email;
END$$


CREATE TRIGGER aggiorna_affidabilita_finanziamento
AFTER INSERT ON Finanziamento
FOR EACH ROW
BEGIN
    UPDATE Creatore
    SET affidabilità = affidabilità + 1
    WHERE utente_email = (
        SELECT creatore_email 
        FROM Progetto 
        WHERE nome = NEW.nome_progetto
    );
END$$


-- Trigger : Incremento Numero Progetti Creatore

CREATE TRIGGER incrementa_numero_progetti
AFTER INSERT ON Progetto
FOR EACH ROW
BEGIN
    UPDATE Creatore
    SET nr_progetti = nr_progetti + 1
    WHERE utente_email = NEW.creatore_email;
END$$

DELIMITER ;


-- Trigger: Chiusura automatica progetti scaduti


SET GLOBAL event_scheduler = ON;

CREATE EVENT chiusura_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    UPDATE Progetto
    SET stato = 'chiuso'
    WHERE data_limite < CURDATE()
      AND stato = 'aperto';
