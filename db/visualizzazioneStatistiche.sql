DELIMITER $$

CREATE PROCEDURE CreatoriAffidabili()
BEGIN
    SELECT u.nickname,
           (COUNT(DISTINCT f.nome_progetto) / COUNT(DISTINCT p.nome)) * 100 AS affidabilita_percentuale
    FROM Creatore c
    JOIN Utente u ON u.email = c.utente_email
    JOIN Progetto p ON p.creatore_email = c.utente_email
    LEFT JOIN Finanziamento f ON f.nome_progetto = p.nome
    GROUP BY c.utente_email
    ORDER BY affidabilita_percentuale DESC
    LIMIT 3;
END$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE ProgettiViciniBudget()
BEGIN
    SELECT p.nome,
           p.budget,
           IFNULL(SUM(f.importo), 0) AS totale_finanziamenti,
           (p.budget - IFNULL(SUM(f.importo), 0)) AS differenza
    FROM Progetto p
    LEFT JOIN Finanziamento f ON f.nome_progetto = p.nome
    WHERE p.stato = 'aperto'
    GROUP BY p.nome
    ORDER BY differenza ASC
    LIMIT 3;
END$$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE TopUtentiFinanziatori()
BEGIN
    SELECT u.nickname,
           SUM(f.importo) AS totale_finanziato
    FROM Finanziamento f
    JOIN Utente u ON u.email = f.email_utente
    GROUP BY f.email_utente
    ORDER BY totale_finanziato DESC
    LIMIT 3;
END$$

DELIMITER ;


