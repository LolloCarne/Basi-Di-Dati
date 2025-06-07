/*http://localhost/public/register.php*/
create TABLE Utente (

email VARCHAR(255) PRIMARY KEY,
nickname VARCHAR(255) NOT  NULL UNIQUE,
password VARCHAR(255) NOT  NULL,
nome VARCHAR(50) NOT  NULL,
cognome  VARCHAR(50) NOT  NULL,
anno_nascita INT,
luogo_nascita VARCHAR(100),
codice_sicurezza VARCHAR(255) DEFAULT NULL
);

CREATE TABLE Creatore (
    utente_email VARCHAR(255) PRIMARY KEY,
    nr_progetti INT DEFAULT 0,
    affidabilità DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (utente_email) REFERENCES Utente(email)
);


CREATE TABLE Skill (
id INT AUTOINCREMENT PRIMARY KEY,
competenza VARCHAR(50) NOT  NULL UNIQUE 
);

CREATE TABLE UtenteSkill ( 
utente_email VARCHAR(255), 
competenza INT not null, 
livello INT CHECK (livello BETWEEN 0 AND 5), 
PRIMARY KEY (utente_email, competenza), 
FOREIGN KEY (utente_email) 
REFERENCES Utente(email), 
FOREIGN KEY (competenza) REFERENCES Skill(id) -- cambiata la referenza sulla tabellaid
);


create TABLE Progetto (

nome VARCHAR(255) PRIMARY KEY,
creatore_email VARCHAR(255),
descrizione TEXT,
data_inserimento DATE NOT  NULL,
budget DECIMAL(15,2) NOT  NULL CHECK (budget > 0),
data_limite DATE NOT NULL,
stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
tipo_progetto ENUM('hardware', 'software') NOT NULL,
FOREIGN KEY (creatore_email) REFERENCES Creatore(utente_email)

);
CREATE TABLE Foto (
    file_name VARCHAR(255) PRIMARY KEY,
    path TEXT NOT NULL,
    nome_progetto VARCHAR(255),
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome)
);

CREATE TABLE Componenti (
    nome VARCHAR(255) PRIMARY KEY,
    descrizione TEXT,
    prezzo DECIMAL(15,2) NOT NULL CHECK (prezzo > 0),
    quantità INT NOT NULL CHECK (quantità >= 0)
);

CREATE TABLE ComponentiProgetto (
    nome_progetto VARCHAR(255),
    nome_componenti VARCHAR(255),
    PRIMARY KEY (nome_progetto, nome_componenti),
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome),
    FOREIGN KEY (nome_componenti) REFERENCES Componenti(nome)
);

CREATE TABLE Reward (
    codice VARCHAR(50) PRIMARY KEY,
    descrizione TEXT,
    foto VARCHAR(255)
);

CREATE TABLE RewardProgetto (
    id_progetto VARCHAR(255),
    codice_reward VARCHAR(50),
    PRIMARY KEY (id_progetto, codice_reward),
    FOREIGN KEY (id_progetto) REFERENCES Progetto(nome),
    FOREIGN KEY (codice_reward) REFERENCES Reward(codice)
);

CREATE TABLE RewardFinanziamento (
    email_utente VARCHAR(255),
    nome_progetto VARCHAR(255),
    data DATE,
    codice_reward VARCHAR(50),
    PRIMARY KEY (email_utente, nome_progetto, data),
    FOREIGN KEY (email_utente, nome_progetto, data) REFERENCES Finanziamento(email_utente, nome_progetto, data),
    FOREIGN KEY (codice_reward) REFERENCES Reward(codice)
);

CREATE TABLE ProfiloCompetenze (
    nome VARCHAR(255) PRIMARY KEY,
    competenza VARCHAR(50),
    livello INT CHECK (livello BETWEEN 0 AND 5),
    FOREIGN KEY (competenza) REFERENCES Skill(competenza)
);

CREATE TABLE Finanziamento (
    email_utente VARCHAR(255),
    nome_progetto VARCHAR(255),
    data DATE NOT NULL,
    importo DECIMAL(15,2) NOT NULL CHECK (importo > 0),
    PRIMARY KEY (email_utente, nome_progetto, data),
    FOREIGN KEY (email_utente) REFERENCES Utente(email),
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome)
);

CREATE TABLE Commento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenuto TEXT NOT NULL,
    id_utente VARCHAR(255),
    nome_progetto VARCHAR(255),
    FOREIGN KEY (id_utente) REFERENCES Utente(email),
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome)
);

CREATE TABLE Risposta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_creatore VARCHAR(255),
    id_commento INT,
    testo TEXT NOT NULL,
    FOREIGN KEY (email_creatore) REFERENCES Utente(email),
    FOREIGN KEY (id_commento) REFERENCES Commento(id)
);

CREATE TABLE Candidatura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_utente VARCHAR(255),
    nome_profilo VARCHAR(255),
    nome_progetto VARCHAR(255),
    FOREIGN KEY (email_utente) REFERENCES Utente(email),
    FOREIGN KEY (nome_profilo) REFERENCES ProfiloCompetenze(nome),
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome)
);
