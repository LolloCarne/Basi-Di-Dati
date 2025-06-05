/* 
CREATE TABLE RewardFinanziamento (
    email_utente VARCHAR(255),
    nome_progetto VARCHAR(255),
    data DATE,
    codice_reward VARCHAR(50),
    PRIMARY KEY (email_utente, nome_progetto, data),
    FOREIGN KEY (email_utente, nome_progetto, data) REFERENCES Finanziamento(email_utente, nome_progetto, data),
    FOREIGN KEY (codice_reward) REFERENCES Reward(codice)
);

DELIMITER $$

CREATE PROCEDURE FinanziaProgetto(
    IN p_email_utente VARCHAR(255),
    IN p_nome_progetto VARCHAR(255),
    IN p_importo DECIMAL(15,2),
    IN p_codice_reward VARCHAR(50)
)
BEGIN
    DECLARE v_data DATE;
    DECLARE v_stato ENUM('aperto', 'chiuso');
    DECLARE v_reward_count INT;

    // Verifica che il progetto esista e sia aperto
    SELECT stato INTO v_stato
    FROM Progetto
    WHERE nome = p_nome_progetto;

    IF v_stato IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Progetto non esistente.';
    ELSEIF v_stato <> 'aperto' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Il progetto non è aperto a finanziamenti.';
    END IF;

     //  2. Verifica che la reward sia associata al progetto
    SELECT COUNT(*) INTO v_reward_count
    FROM RewardProgetto
    WHERE id_progetto = p_nome_progetto AND codice_reward = p_codice_reward;

    IF v_reward_count = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reward non associata al progetto.';
    END IF;

     // 3. Salva il finanziamento
    SET v_data = CURDATE();

    INSERT INTO Finanziamento(email_utente, nome_progetto, data, importo)
    VALUES (p_email_utente, p_nome_progetto, v_data, p_importo);

    //  4. Collega il finanziamento alla reward
    INSERT INTO RewardFinanziamento(email_utente, nome_progetto, data, codice_reward)
    VALUES (p_email_utente, p_nome_progetto, v_data, p_codice_reward);
END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER chiudi_progetto
AFTER INSERT ON Finanziamento
FOR EACH ROW
BEGIN
    DECLARE v_budget DECIMAL(15,2);
    DECLARE v_totale_finanziato DECIMAL(15,2);
    DECLARE v_data_limite DATE;
    DECLARE v_stato_attuale ENUM('aperto', 'chiuso');

    -//  Ottieni dati del progetto
    SELECT budget, data_limite, stato
    INTO v_budget, v_data_limite, v_stato_attuale
    FROM Progetto
    WHERE nome = NEW.nome_progetto;

    //  Calcola il totale finanziato finora
    SELECT SUM(importo)
    INTO v_totale_finanziato
    FROM Finanziamento
    WHERE nome_progetto = NEW.nome_progetto;

    // Se il budget è raggiunto o la data è superata e il progetto è ancora aperto → chiudi
    IF (v_totale_finanziato >= v_budget OR CURDATE() > v_data_limite)
       AND v_stato_attuale = 'aperto' THEN

        UPDATE Progetto
        SET stato = 'chiuso'
        WHERE nome = NEW.nome_progetto;

    END IF;
END$$

DELIMITER ;


*/