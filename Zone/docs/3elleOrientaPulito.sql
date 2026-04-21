CREATE DATABASE TreElleOrienta;
USE TreElleOrienta;

-- tabella Foto
CREATE TABLE Foto (
	ID_foto INT PRIMARY KEY AUTO_INCREMENT,
	path_foto VARCHAR(500) NOT NULL,
	data_eliminazione DATETIME
) ENGINE = InnoDB;

CREATE TABLE Scuole (
	COD_meccanografico VARCHAR(10) PRIMARY KEY,
	nome VARCHAR(50) NOT NULL,
	descrizione TEXT NOT NULL,
	via VARCHAR(30) NOT NULL,
	n_civico INT NOT NULL,
	id_citta INT NOT NULL,
	coordinate POINT NOT NULL,
	id_foto INT,
	CONSTRAINT fk_FotoScuola FOREIGN KEY (id_foto) REFERENCES Foto(ID_foto)
) ENGINE=InnoDB;

CREATE TABLE Ambiti (
	ID_ambito INT PRIMARY KEY AUTO_INCREMENT,
	nome VARCHAR(50) NOT NULL,
	descrizione TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Scuole_Ambiti (
	cod_scuola VARCHAR(10),
	id_ambito INT,
	PRIMARY KEY (cod_scuola, id_ambito),
	CONSTRAINT fk_ScuolaAmb FOREIGN KEY (cod_scuola) REFERENCES Scuole(COD_meccanografico),
	CONSTRAINT fk_AmbitoSc FOREIGN KEY (id_ambito) REFERENCES Ambiti(ID_ambito)
) ENGINE=InnoDB;

CREATE TABLE Indirizzi_studio (
	ID_indirizzo INT PRIMARY KEY AUTO_INCREMENT,
	nome VARCHAR(50) NOT NULL,
	ordine INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Scuole_Indirizzi (
	cod_scuola VARCHAR(10),
	id_indirizzo INT,
	n_ordine INT,
	PRIMARY KEY (cod_scuola, id_indirizzo),
	CONSTRAINT fk_ScuolaInd FOREIGN KEY (cod_scuola) REFERENCES Scuole(COD_meccanografico),
	CONSTRAINT fk_IndirizzoSc FOREIGN KEY (id_indirizzo) REFERENCES Indirizzi_studio(ID_indirizzo)
) ENGINE=InnoDB;

-- Creazione tabella province
 CREATE TABLE Province (
	sigla VARCHAR(2) NOT NULL,
	nome VARCHAR(70) NOT NULL,
	PRIMARY KEY (sigla)
 ) ENGINE = InnoDB;
 
 -- Creazione tabella Zone
 CREATE TABLE Zone (
	ID_zona INT NOT NULL AUTO_INCREMENT,
	nome VARCHAR(30) NOT NULL,
	PRIMARY KEY (ID_zona)
 ) ENGINE = InnoDB;

-- Creazione tabella Citta
 CREATE TABLE Citta (
	ID_citta INT NOT NULL AUTO_INCREMENT,
	nome VARCHAR(50) NOT NULL,
	sigla_provincia VARCHAR(2) NOT NULL,
	id_zona INT NOT NULL ,
	CONSTRAINT fk_sigla_provincia FOREIGN KEY (sigla_provincia) REFERENCES Province(sigla),
	CONSTRAINT fk_id_zona FOREIGN KEY (id_zona) REFERENCES Zone(ID_zona),
	PRIMARY KEY (ID_citta)
 ) ENGINE = InnoDB;

CREATE TABLE Eventi (
    ID_evento INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(50) NOT NULL,
    descrizione TEXT NOT NULL,
    target ENUM('TERRITORIALE', 'SCOLASTICO'),
    ora_inizio DATETIME NOT NULL,
    ora_fine DATETIME NOT NULL,
    visibile BOOLEAN NOT NULL,
    prenotabile BOOLEAN NOT NULL,
    via_P VARCHAR(50),
    n_civico_P INT,
	coordinate POINT,
    descrizione_breve VARCHAR(100) NOT NULL,
    data_eliminazione DATETIME,
    id_citta INT,
    cod_scuola VARCHAR(10),
    id_foto INT NOT NULL,
    CONSTRAINT fk_CittaEv FOREIGN KEY (id_citta) REFERENCES Citta(ID_citta),
    CONSTRAINT fk_ScuolaEv FOREIGN KEY (cod_scuola) REFERENCES Scuole(COD_meccanografico),
    CONSTRAINT fk_FotoEv FOREIGN KEY (id_foto) REFERENCES Foto(ID_foto),
	
    CONSTRAINT check_indirizzo
    CHECK (
        cod_scuola IS NOT NULL
        OR (
            cod_scuola IS NULL 
            AND via_P IS NOT NULL 
            AND n_civico_P IS NOT NULL
            AND coordinate IS NOT NULL
            AND id_citta IS NOT NULL
        )
    )
) ENGINE = InnoDB;

-- tabella Progetti
CREATE TABLE Progetti (
	ID_progetto INT PRIMARY KEY AUTO_INCREMENT,
	titolo VARCHAR(50) NOT NULL,
	descrizione TEXT NOT NULL,
	n_ordine INT NOT NULL UNIQUE,
	data_eliminazione DATE,
	id_foto INT,
	CONSTRAINT fk_fotoProgetti FOREIGN KEY (id_foto) REFERENCES Foto(ID_foto)
) ENGINE = InnoDB;

-- tabella Links
CREATE TABLE Links (
	ID_link INT PRIMARY KEY AUTO_INCREMENT,
	titolo VARCHAR(50) NOT NULL,
	descrizione TEXT NOT NULL,
	n_ordine INT NOT NULL UNIQUE,
	data_eliminazione DATE,
	id_foto INT,
	CONSTRAINT fk_fotoLinks FOREIGN KEY (id_foto) REFERENCES Foto(ID_foto)
) ENGINE = InnoDB;

-- Creazione tabella Utenti
CREATE TABLE Utenti (
	ID_utente INT NOT NULL AUTO_INCREMENT,
	username VARCHAR(32) NOT NULL UNIQUE,
	hash_password VARCHAR(128) NOT NULL,
	email VARCHAR(254) NOT NULL UNIQUE,
	tipo ENUM('ADMIN', 'SCOLASTICO') NOT NULL,
	stato ENUM('ATTIVO', 'BLOCCATO') NOT NULL,
	cod_scuola VARCHAR(10) NOT NULL,
	CONSTRAINT fk_scuola FOREIGN KEY (cod_scuola) REFERENCES Scuole(COD_meccanografico),
	PRIMARY KEY (`ID_utente`)
 ) ENGINE = InnoDB;
 
 

-- Creazione trigger separato Eventi
DELIMITER //
CREATE TRIGGER default_target
BEFORE INSERT ON Eventi
FOR EACH ROW
BEGIN
    IF NEW.cod_scuola IS NULL THEN
        SET NEW.target = 'TERRITORIALE';
    ELSE
        SET NEW.target = 'SCOLASTICO';
        SET NEW.via_P = NULL;
        SET NEW.n_civico_P = NULL;
        SET NEW.id_citta = NULL;
        SET NEW.coordinate = NULL;
    END IF;
END;
//
DELIMITER ;


/*
Query Eventi
Inserimento con e senza scuola:
INSERT INTO Eventi(titolo, descrizione, ora_inizio, ora_fine, visibile, prenotabile, descrizione_breve, cod_scuola, id_foto) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?);
INSERT INTO Eventi(titolo, descrizione, ora_inizio, ora_fine, visibile, prenotabile, via_P, n_civico_P, longitudine, latitudine, descrizione_breve, id_citta, id_foto) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);

Aggiornamento con e senza scuola
UPDATE Eventi SET titolo=?, descrizione=?, ora_inizio=?, ora_fine=?, visibile=?, prenotabile=?, descrizione_breve=?, cod_scuola=?, id_foto=? WHERE ID_evento=?;
UPDATE Eventi SET titolo=?, descrizione=?, ora_inizio=?, ora_fine=?, visibile=?, prenotabile=?, via_P=?, n_civico_P=?, longitudine=?, latitudine=?, descrizione_breve=?, id_citta=?, id_foto=? WHERE ID_evento=?;

Eliminazione
UPDATE Eventi SET data_eliminazione=CURDATE() WHERE ID_evento=?;

Estrazione:
SELECT * FROM Eventi 
LEFT JOIN Citta ON id_citta=ID_citta 
LEFT JOIN Scuole ON cod_scuola=COD_scuola 
LEFT JOIN Foto ON id_foto=ID_foto 
WHERE Eventi.data_eliminazione IS NULL;
*/