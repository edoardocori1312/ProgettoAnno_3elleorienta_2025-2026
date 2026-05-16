-- ============================================================
-- Schema: treelleorienta
-- Cleaned from Zone/docs/3elleOrientaPulito.sql
-- Changes: POINT columns -> DECIMAL lat/lng, sito on Scuole,
--   indirizzo on Links, nullable cod_scuola on Utenti,
--   trigger removed (logic in PHP), tables reordered for FK order,
--   DB name lowercase, check_indirizzo updated for lat/lng.
-- ============================================================

CREATE DATABASE IF NOT EXISTS treelleorienta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE treelleorienta;

CREATE TABLE Foto (
    ID_foto     INT          PRIMARY KEY AUTO_INCREMENT,
    path_foto   VARCHAR(500) NOT NULL,
    data_eliminazione DATETIME
) ENGINE=InnoDB;

CREATE TABLE Province (
    sigla VARCHAR(2)  NOT NULL,
    nome  VARCHAR(70) NOT NULL,
    PRIMARY KEY (sigla)
) ENGINE=InnoDB;

CREATE TABLE Zone (
    ID_zona INT         NOT NULL AUTO_INCREMENT,
    nome    VARCHAR(30) NOT NULL,
    PRIMARY KEY (ID_zona)
) ENGINE=InnoDB;

CREATE TABLE Citta (
    ID_citta         INT         NOT NULL AUTO_INCREMENT,
    nome             VARCHAR(50) NOT NULL,
    sigla_provincia  VARCHAR(2)  NOT NULL,
    id_zona          INT         NOT NULL,
    CONSTRAINT fk_CittaProv FOREIGN KEY (sigla_provincia) REFERENCES Province(sigla),
    CONSTRAINT fk_CittaZona FOREIGN KEY (id_zona)         REFERENCES Zone(ID_zona),
    PRIMARY KEY (ID_citta)
) ENGINE=InnoDB;

CREATE TABLE Ambiti (
    ID_ambito   INT          PRIMARY KEY AUTO_INCREMENT,
    nome        VARCHAR(50)  NOT NULL,
    descrizione TEXT         NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Indirizzi_studio (
    ID_indirizzo INT         PRIMARY KEY AUTO_INCREMENT,
    nome         VARCHAR(50) NOT NULL,
    ordine       INT         NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Scuole (
    COD_meccanografico VARCHAR(10)  PRIMARY KEY,
    nome               VARCHAR(50)  NOT NULL,
    descrizione        TEXT         NOT NULL,
    via                VARCHAR(30)  NOT NULL,
    n_civico           INT          NOT NULL,
    id_citta           INT          NOT NULL,
    sito               VARCHAR(255),
    latitudine         DECIMAL(9,6),
    longitudine        DECIMAL(9,6),
    id_foto            INT,
    CONSTRAINT fk_ScuolaCitta FOREIGN KEY (id_citta) REFERENCES Citta(ID_citta),
    CONSTRAINT fk_FotoScuola  FOREIGN KEY (id_foto)  REFERENCES Foto(ID_foto)
) ENGINE=InnoDB;

CREATE TABLE Scuole_Ambiti (
    cod_scuola VARCHAR(10) NOT NULL,
    id_ambito  INT         NOT NULL,
    PRIMARY KEY (cod_scuola, id_ambito),
    CONSTRAINT fk_ScuolaAmb FOREIGN KEY (cod_scuola) REFERENCES Scuole(COD_meccanografico),
    CONSTRAINT fk_AmbitoSc  FOREIGN KEY (id_ambito)  REFERENCES Ambiti(ID_ambito)
) ENGINE=InnoDB;

CREATE TABLE Scuole_Indirizzi (
    cod_scuola   VARCHAR(10) NOT NULL,
    id_indirizzo INT         NOT NULL,
    n_ordine     INT,
    PRIMARY KEY (cod_scuola, id_indirizzo),
    CONSTRAINT fk_ScuolaInd  FOREIGN KEY (cod_scuola)   REFERENCES Scuole(COD_meccanografico),
    CONSTRAINT fk_IndirizzoSc FOREIGN KEY (id_indirizzo) REFERENCES Indirizzi_studio(ID_indirizzo)
) ENGINE=InnoDB;

CREATE TABLE Eventi (
    ID_evento         INT          PRIMARY KEY AUTO_INCREMENT,
    titolo            VARCHAR(50)  NOT NULL,
    descrizione       TEXT         NOT NULL,
    descrizione_breve VARCHAR(100) NOT NULL,
    target            ENUM('TERRITORIALE','SCOLASTICO'),
    ora_inizio        DATETIME     NOT NULL,
    ora_fine          DATETIME     NOT NULL,
    visibile          BOOLEAN      NOT NULL DEFAULT 1,
    prenotabile       BOOLEAN      NOT NULL DEFAULT 0,
    via_P             VARCHAR(50),
    n_civico_P        INT,
    latitudine        DECIMAL(9,6),
    longitudine       DECIMAL(9,6),
    data_eliminazione DATETIME,
    id_citta          INT,
    cod_scuola        VARCHAR(10),
    id_foto           INT          NOT NULL,
    CONSTRAINT fk_CittaEv  FOREIGN KEY (id_citta)   REFERENCES Citta(ID_citta),
    CONSTRAINT fk_ScuolaEv FOREIGN KEY (cod_scuola) REFERENCES Scuole(COD_meccanografico),
    CONSTRAINT fk_FotoEv   FOREIGN KEY (id_foto)    REFERENCES Foto(ID_foto),
    CONSTRAINT check_indirizzo CHECK (
        cod_scuola IS NOT NULL
        OR (
            cod_scuola   IS NULL
            AND via_P        IS NOT NULL
            AND n_civico_P   IS NOT NULL
            AND latitudine   IS NOT NULL
            AND longitudine  IS NOT NULL
            AND id_citta     IS NOT NULL
        )
    )
) ENGINE=InnoDB;

CREATE TABLE Progetti (
    ID_progetto       INT         PRIMARY KEY AUTO_INCREMENT,
    titolo            VARCHAR(50) NOT NULL,
    descrizione       TEXT        NOT NULL,
    n_ordine          INT         NOT NULL UNIQUE,
    data_eliminazione DATE,
    id_foto           INT,
    CONSTRAINT fk_FotoProgetti FOREIGN KEY (id_foto) REFERENCES Foto(ID_foto)
) ENGINE=InnoDB;

CREATE TABLE Links (
    ID_link           INT          PRIMARY KEY AUTO_INCREMENT,
    titolo            VARCHAR(50)  NOT NULL,
    descrizione       TEXT         NOT NULL,
    indirizzo         VARCHAR(500) NOT NULL,
    n_ordine          INT          NOT NULL UNIQUE,
    data_eliminazione DATE,
    id_foto           INT,
    CONSTRAINT fk_FotoLinks FOREIGN KEY (id_foto) REFERENCES Foto(ID_foto)
) ENGINE=InnoDB;

CREATE TABLE Utenti (
    ID_utente     INT          NOT NULL AUTO_INCREMENT,
    username      VARCHAR(32)  NOT NULL UNIQUE,
    hash_password VARCHAR(128) NOT NULL,
    email         VARCHAR(254) NOT NULL UNIQUE,
    tipo          ENUM('ADMIN','SCOLASTICO') NOT NULL,
    stato         ENUM('ATTIVO','BLOCCATO')  NOT NULL DEFAULT 'ATTIVO',
    cod_scuola    VARCHAR(10),
    CONSTRAINT fk_UtentiScuola FOREIGN KEY (cod_scuola) REFERENCES Scuole(COD_meccanografico),
    PRIMARY KEY (ID_utente)
) ENGINE=InnoDB;
