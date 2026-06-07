-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Giu 06, 2026 alle 09:06
-- Versione del server: 10.11.16-MariaDB-cll-lve-log
-- Versione PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uawit4pc_3elleorienta`
--

DELIMITER $$
--
-- Procedure
--
CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `AddEventoScuola` (IN `_titolo` VARCHAR(255), IN `_descrizione` TEXT, IN `_ora_inizio` DATETIME, IN `_ora_fine` DATETIME, IN `_visibile` BOOLEAN, IN `_prenotabile` BOOLEAN, IN `_descrizione_breve` VARCHAR(255), IN `_cod_scuola` VARCHAR(10), IN `_id_foto` INT)   BEGIN
    INSERT INTO Eventi(
        titolo, descrizione, ora_inizio, ora_fine,
        visibile, prenotabile, descrizione_breve,
        cod_scuola, id_foto
    )
    VALUES(
        _titolo, _descrizione, _ora_inizio, _ora_fine,
        _visibile, _prenotabile, _descrizione_breve,
        _cod_scuola, _id_foto
    );
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `AddEventoTerritorio` (IN `_titolo` VARCHAR(255), IN `_descrizione` TEXT, IN `_ora_inizio` DATETIME, IN `_ora_fine` DATETIME, IN `_visibile` BOOLEAN, IN `_prenotabile` BOOLEAN, IN `_via_P` VARCHAR(255), IN `_n_civico_P` VARCHAR(10), IN `_coordinate` POINT, IN `_descrizione_breve` VARCHAR(255), IN `_id_citta` INT, IN `_cod_scuola` VARCHAR(10), IN `_id_foto` INT)   BEGIN
    INSERT INTO Eventi(
        titolo, descrizione, ora_inizio, ora_fine,
        visibile, prenotabile, via_P, n_civico_P,
        coordinate, descrizione_breve,
        id_citta, cod_scuola, id_foto
    )
    VALUES(
        _titolo, _descrizione, _ora_inizio, _ora_fine,
        _visibile, _prenotabile, _via_P, _n_civico_P,
        _coordinate, _descrizione_breve,
        _id_citta, _cod_scuola, _id_foto
    );
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `AddLink` (IN `_titolo` VARCHAR(50), IN `_url_link` VARCHAR(100), IN `_descrizione` TEXT, IN `_n_ordine` INT, IN `_id_foto` INT)   BEGIN
    IF EXISTS (
        SELECT 1
        FROM Links
        WHERE n_ordine = _n_ordine
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Esiste già un link con questo ordine';
    END IF;

    INSERT INTO Links (
        titolo,
        url_link,
        descrizione,
        n_ordine,
        data_eliminazione,
        id_foto
    )
    VALUES (
        _titolo,
        _url_link,
        _descrizione,
        _n_ordine,
        NULL,
        _id_foto
    );
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `AddProgetto` (IN `_titolo` VARCHAR(70), IN `_descrizione` TEXT, IN `_n_ordine` INT, IN `_id_foto` INT)   BEGIN
    IF EXISTS (
        SELECT 1
        FROM Progetti
        WHERE n_ordine = _n_ordine
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Esiste già un progetto con questo ordine';
    END IF;

    INSERT INTO Progetti (
        titolo,
        descrizione,
        n_ordine,
        data_eliminazione,
        id_foto
    )
    VALUES (
        _titolo,
        _descrizione,
        _n_ordine,
        NULL,
        _id_foto
    );
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `AddScuola` (IN `_cod_meccanografico` VARCHAR(10), IN `_nome` VARCHAR(50), IN `_descrizione` TEXT, IN `_sito` VARCHAR(100), IN `_via` VARCHAR(30), IN `_n_civico` INT, IN `_id_citta` INT, IN `_coordinate` POINT, IN `_id_foto` INT)   BEGIN
    -- Controllo duplicato
    IF EXISTS (
        SELECT 1 FROM Scuole 
        WHERE COD_meccanografico = _cod_meccanografico
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Scuola già esistente';
    END IF;

    INSERT INTO Scuole (
        COD_meccanografico,
        nome,
        descrizione,
        sito,
        via,
        n_civico,
        id_citta,
        coordinate,
        id_foto
    )
    VALUES (
        _cod_meccanografico,
        _nome,
        _descrizione,
        _sito,
        _via,
        _n_civico,
        _id_citta,
        _coordinate,
        _id_foto
    );
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `AddUtenti` (IN `_username` VARCHAR(32), IN `_hashPassword` VARCHAR(128), IN `_email` VARCHAR(254), IN `_tipo` ENUM('ADMIN','SCOLASTICO'), IN `_stato` ENUM('ATTIVO','BLOCCATO'), IN `_codScuola` VARCHAR(10))   BEGIN
	INSERT INTO Utenti(username, hash_password, email, tipo, stato, cod_scuola) 
	VALUES (_username, _hashPassword, _email, _tipo, _stato, _codScuola);
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `AddZona` (IN `_nome` VARCHAR(30))   BEGIN
    IF EXISTS (
        SELECT 1
        FROM Zone
        WHERE nome = _nome
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Zona già esistente';
    END IF;

    INSERT INTO Zone (nome)
    VALUES (_nome);
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `CambiaStatoUtente` (IN `_id_utente` INT, IN `_nuovo_stato` VARCHAR(10))   BEGIN
    UPDATE Utenti
    SET stato = _nuovo_stato
    WHERE ID_utente = _id_utente;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `CollegaCittaZona` (IN `_id_citta` INT, IN `_id_zona` INT)   BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM Citta
        WHERE ID_citta = _id_citta
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Città non trovata';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM Zone
        WHERE ID_zona = _id_zona
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Zona non trovata';
    END IF;

    UPDATE Citta
    SET id_zona = _id_zona
    WHERE ID_citta = _id_citta;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `CollegaScuolaAmbito` (IN `_cod_scuola` VARCHAR(10), IN `_id_ambito` INT)   BEGIN
    INSERT IGNORE INTO Scuole_Ambiti (cod_scuola, id_ambito)
    VALUES (_cod_scuola, _id_ambito);
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `CollegaScuolaIndirizzo` (IN `_cod_scuola` VARCHAR(10), IN `_id_indirizzo` INT, IN `_n_ordine` INT)   BEGIN
    INSERT IGNORE INTO Scuole_Indirizzi (cod_scuola, id_indirizzo, n_ordine)
    VALUES (_cod_scuola, _id_indirizzo, _n_ordine);
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `DelEvento` (IN `_id_evento` INT)   BEGIN
    UPDATE Eventi
    SET data_eliminazione = CURDATE()
    WHERE ID_evento = _id_evento;


    IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Evento non trovato';
    END IF;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `DelLink` (IN `_id_link` INT)   BEGIN
    UPDATE Links
    SET data_eliminazione = CURDATE()
    WHERE ID_link = _id_link;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `DelProgetto` (IN `_id_progetto` INT)   BEGIN
    UPDATE Progetti
    SET data_eliminazione = CURDATE()
    WHERE ID_progetto = _id_progetto;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `DelUtente` (`_username` VARCHAR(32))   BEGIN
	DELETE FROM Utenti WHERE username = _username;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `DelZona` (IN `_id_zona` INT)   BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM Zone
        WHERE ID_zona = _id_zona
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Zona non trovata';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM Citta
        WHERE id_zona = _id_zona
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Impossibile eliminare: la zona è ancora associata ad almeno una città';
    END IF;

    DELETE FROM Zone
    WHERE ID_zona = _id_zona;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetEventiPerScuola` (IN `_cod_scuola` VARCHAR(10))   BEGIN
    SELECT ID_evento, titolo, descrizione, target, ora_inizio, ora_fine,
           visibile, prenotabile, via_P, n_civico_P, coordinate,
           descrizione_breve, id_citta, cod_scuola, id_foto
    FROM Eventi
    WHERE data_eliminazione IS NULL
      AND cod_scuola = _cod_scuola
    ORDER BY ora_inizio;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetEventiTerritorialiPerCitta` (IN `_id_citta` INT)   BEGIN
    SELECT ID_evento, titolo, descrizione, target, ora_inizio, ora_fine,
           visibile, prenotabile, via_P, n_civico_P, coordinate,
           descrizione_breve, id_citta, cod_scuola, id_foto
    FROM Eventi
    WHERE data_eliminazione IS NULL
      AND cod_scuola IS NULL
      AND id_citta = _id_citta
    ORDER BY ora_inizio;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetFotoAttive` ()   BEGIN
    SELECT ID_foto, path_foto
    FROM Foto
    WHERE data_eliminazione IS NULL
    ORDER BY ID_foto;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetLinksAttivi` ()   BEGIN
    SELECT ID_link, titolo, url_link, descrizione, n_ordine, id_foto
    FROM Links
    WHERE data_eliminazione IS NULL
    ORDER BY n_ordine;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetProgettiAttivi` ()   BEGIN
    SELECT ID_progetto, titolo, descrizione, n_ordine, id_foto
    FROM Progetti
    WHERE data_eliminazione IS NULL
    ORDER BY n_ordine;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetScuolaRuoloUtente` (`_id_utente` INT(11))   BEGIN
	SELECT tipo, cod_scuola FROM Utenti WHERE ID_utente = _id_utente;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetScuolePerAmbito` (IN `_id_ambito` INT)   BEGIN
    SELECT s.COD_meccanografico, s.nome, s.descrizione, s.sito, s.via, s.n_civico, s.id_citta, s.coordinate, s.id_foto
    FROM Scuole s
    INNER JOIN Scuole_Ambiti sa ON sa.cod_scuola = s.COD_meccanografico
    WHERE sa.id_ambito = _id_ambito;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetScuolePerCitta` (IN `_id_citta` INT)   BEGIN
    SELECT COD_meccanografico, nome, descrizione, sito, via, n_civico, id_citta, coordinate, id_foto
    FROM Scuole
    WHERE id_citta = _id_citta;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetScuolePerIndirizzo` (IN `_id_indirizzo` INT)   BEGIN
    SELECT s.COD_meccanografico, s.nome, s.descrizione, s.sito, s.via, s.n_civico, s.id_citta, s.coordinate, s.id_foto, si.n_ordine
    FROM Scuole s
    INNER JOIN Scuole_Indirizzi si ON si.cod_scuola = s.COD_meccanografico
    WHERE si.id_indirizzo = _id_indirizzo
    ORDER BY si.n_ordine;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `GetZoneConNumeroCitta` ()   BEGIN
    SELECT 
        z.ID_zona,
        z.nome,
        COUNT(c.ID_citta) AS numero_citta
    FROM Zone z
    LEFT JOIN Citta c ON c.id_zona = z.ID_zona
    GROUP BY z.ID_zona, z.nome
    ORDER BY z.nome;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `ModificaLink` (IN `_id_link` INT, IN `_titolo` VARCHAR(50), IN `_url_link` VARCHAR(100), IN `_descrizione` TEXT, IN `_n_ordine` INT, IN `_id_foto` INT)   BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM Links
        WHERE ID_link = _id_link
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Link non trovato';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM Links
        WHERE n_ordine = _n_ordine
          AND ID_link <> _id_link
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Esiste già un altro link con questo ordine';
    END IF;

    UPDATE Links
    SET
        titolo = _titolo,
        url_link = _url_link,
        descrizione = _descrizione,
        n_ordine = _n_ordine,
        id_foto = _id_foto
    WHERE ID_link = _id_link;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `ModificaProgetto` (IN `_id_progetto` INT, IN `_titolo` VARCHAR(70), IN `_descrizione` TEXT, IN `_n_ordine` INT, IN `_id_foto` INT)   BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM Progetti
        WHERE ID_progetto = _id_progetto
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Progetto non trovato';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM Progetti
        WHERE n_ordine = _n_ordine
          AND ID_progetto <> _id_progetto
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Esiste già un altro progetto con questo ordine';
    END IF;

    UPDATE Progetti
    SET
        titolo = _titolo,
        descrizione = _descrizione,
        n_ordine = _n_ordine,
        id_foto = _id_foto
    WHERE ID_progetto = _id_progetto;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `ModificaScuola` (IN `_cod_meccanografico` VARCHAR(10), IN `_nome` VARCHAR(50), IN `_descrizione` TEXT, IN `_sito` VARCHAR(100), IN `_via` VARCHAR(30), IN `_n_civico` INT, IN `_id_citta` INT, IN `_coordinate` POINT, IN `_id_foto` INT, IN `_id_ambito` INT, IN `_id_indirizzo` INT, IN `_n_ordine` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF NOT EXISTS (
        SELECT 1
        FROM Scuole
        WHERE COD_meccanografico = _cod_meccanografico
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Scuola non esistente';
    END IF;

    UPDATE Scuole
    SET
        nome = _nome,
        descrizione = _descrizione,
        sito = _sito,
        via = _via,
        n_civico = _n_civico,
        id_citta = _id_citta,
        coordinate = _coordinate,
        id_foto = _id_foto
    WHERE COD_meccanografico = _cod_meccanografico;


    DELETE FROM Scuole_Ambiti
    WHERE cod_scuola = _cod_meccanografico;

    INSERT INTO Scuole_Ambiti (cod_scuola, id_ambito)
    VALUES (_cod_meccanografico, _id_ambito);


    DELETE FROM Scuole_Indirizzi
    WHERE cod_scuola = _cod_meccanografico;

    INSERT INTO Scuole_Indirizzi (cod_scuola, id_indirizzo, n_ordine)
    VALUES (_cod_meccanografico, _id_indirizzo, _n_ordine);

    COMMIT;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `TrovaUsername` (IN `_username` VARCHAR(32))   BEGIN
	SELECT 1 from Utenti where username=_username;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `UpdateEventoScuola` (IN `_id_evento` INT, IN `_titolo` VARCHAR(255), IN `_descrizione` TEXT, IN `_ora_inizio` DATETIME, IN `_ora_fine` DATETIME, IN `_visibile` BOOLEAN, IN `_prenotabile` BOOLEAN, IN `_descrizione_breve` VARCHAR(255), IN `_id_foto` INT)   BEGIN
    UPDATE Eventi
    SET 
        titolo = _titolo,
        descrizione = _descrizione,
        ora_inizio = _ora_inizio,
        ora_fine = _ora_fine,
        visibile = _visibile,
        prenotabile = _prenotabile,
        descrizione_breve = _descrizione_breve,
        id_foto = _id_foto
    WHERE ID_evento = _id_evento;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `UpdateEventoTerritorio` (IN `_id_evento` INT, IN `_titolo` VARCHAR(255), IN `_descrizione` TEXT, IN `_ora_inizio` DATETIME, IN `_ora_fine` DATETIME, IN `_visibile` BOOLEAN, IN `_prenotabile` BOOLEAN, IN `_via_P` VARCHAR(255), IN `_n_civico_P` VARCHAR(10), IN `_coordinate` POINT, IN `_descrizione_breve` VARCHAR(255), IN `_id_citta` INT, IN `_id_foto` INT)   BEGIN
    UPDATE Eventi
    SET 
        titolo = _titolo,
        descrizione = _descrizione,
        ora_inizio = _ora_inizio,
        ora_fine = _ora_fine,
        visibile = _visibile,
        prenotabile = _prenotabile,
        via_P = _via_P,
        n_civico_P = _n_civico_P,
        coordinate = _coordinate,
        descrizione_breve = _descrizione_breve,
        id_citta = _id_citta,
        id_foto = _id_foto
    WHERE ID_evento = _id_evento;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `UpdateUtenteNome` (IN `_username` VARCHAR(32), IN `_newUsername` VARCHAR(32))   BEGIN
	UPDATE Utenti SET username = _newUsername WHERE username = _username;
END$$

CREATE DEFINER=`uawit4pc_3elleorienta`@`localhost` PROCEDURE `UpdateUtentePw` (IN `_username` VARCHAR(32), IN `_newHashPw` VARCHAR(128))   BEGIN
	UPDATE Utenti SET hash_password = _newHashPw WHERE username = _username;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `Ambiti`
--

CREATE TABLE `Ambiti` (
  `ID_ambito` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descrizione` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `Ambiti`
--

INSERT INTO `Ambiti` (`ID_ambito`, `nome`, `descrizione`) VALUES
(1, 'ECONOMIA E TERRITORIO', 'Cittadinanza, giustizia, legalità, contesti internazionali, linguaggio globale, economia, viaggi, geografia economica, politica, innovazione, economia globale, impresa e Stato, beni comuni, economia circolare, connettività, marketing, Internet, organizzazione e cultura imprenditoriale, patrimonio locale e i servizi'),
(2, 'SCIENZA, RICERCA E INNOVAZIONE', 'Logica, coding, matematica, scienze naturali, geologia, biologia, fisica, chimica, meteorologia, astronomia, esperimenti, mondo vegetale e animale'),
(3, 'SOSTENIBILITÀ E BENESSERE', 'Biotecnologie, alimentazione, chimica organica e scienze agroalimentari, alimentazione, benessere, stili di vita, estetica, scienze motorie e sportive\r\n'),
(4, 'SOCIETÀ CONTEMPORANEA', 'Psicologia, sociologia, statistica, antropologia, cura e relazioni con gli altri, aspetti sociali, migrazioni, inclusione, narrazione sociale, associazionismo e volontariato'),
(5, 'ARTE, CULTURA E COMUNICAZIONE', 'Arte, design, architettura, linguaggi multimediali, restauro, colori, spettacolo, musica, comunicazione visuale, moda, grafica, arti decorative, giornalismo, reporting, 3D'),
(6, 'DALL’ALFA ALLA ZED', 'Cultura, libri e documenti antichi, storia e letteratura classica, storia e contemporanea, filosofia, società contemporanea, letteratura e lingue straniere moderne, traduzioni letterarie'),
(7, 'ARTIGIANATO E MESTIERI 4.0', 'Tecniche realizzative, modellazione, studio dei materiali, misure, impianti, attrezzature e macchinari, prototipi, dall’idea al progetto, dal progetto alla realizzazione, meccanica, idraulica, elettronica, elettrotecnica, carpenteria'),
(8, 'TECNICA E TECNOLOGIA', 'Logica, coding e programmazione, software, elettronica, robotica, domotica, meccatronica, progettazione prototipazione e stampa 3D, organizzazione, logistica, trasporti, ingegno e invenzioni'),
(9, 'SOSTENIBILITA’ AMBIENTALE', 'Paesaggio, patrimonio naturale, agricoltura e valore rurale, energia, sicurezza e protezione civile, ambiente marino, ambiente fluviale, territorio montano, mobilità, riprese aeree con i droni, mondo vegetale e animale, architettura'),
(10, 'ACCOGLIENZA, SAPERI E SAPORI', 'Accoglienza, enogastronomia, tipicità, economia, lingua e viaggi, turismo e animazione, decorazione degli ambienti');

-- --------------------------------------------------------

--
-- Struttura della tabella `Citta`
--

CREATE TABLE `Citta` (
  `ID_citta` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `sigla_provincia` varchar(2) NOT NULL,
  `cap` int(11) DEFAULT NULL,
  `id_zona` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Citta`
--

INSERT INTO `Citta` (`ID_citta`, `nome`, `sigla_provincia`, `cap`, `id_zona`) VALUES
(1, 'Jesi', 'AN', 60035, 1),
(2, 'Cingoli', 'MC', 62011, 2),
(1557, 'Acquacanina', 'MC', 62035, NULL),
(1558, 'Acqualagna', 'PU', 61041, NULL),
(1559, 'Acquasanta Terme', 'AP', 63095, NULL),
(1560, 'Acquaviva Picena', 'AP', 63075, NULL),
(1561, 'Agugliano', 'AN', 60020, NULL),
(1562, 'Altidona', 'FM', 63824, NULL),
(1563, 'Amandola', 'FM', 63857, NULL),
(1564, 'Ancona', 'AN', 0, NULL),
(1565, 'Apecchio', 'PU', 61042, NULL),
(1566, 'Apiro', 'MC', 62021, NULL),
(1567, 'Appignano', 'MC', 62010, NULL),
(1568, 'Appignano del Tronto', 'AP', 63083, NULL),
(1569, 'Arcevia', 'AN', 60011, NULL),
(1570, 'Arquata del Tronto', 'AP', 63096, NULL),
(1571, 'Ascoli Piceno', 'AP', 63100, NULL),
(1572, 'Auditore', 'PU', 61020, NULL),
(1573, 'Barbara', 'AN', 60010, NULL),
(1574, 'Barchi', 'PU', 61040, NULL),
(1575, 'Belforte all\'Isauro', 'PU', 61026, NULL),
(1576, 'Belforte del Chienti', 'MC', 62020, NULL),
(1577, 'Belmonte Piceno', 'FM', 63838, NULL),
(1578, 'Belvedere Ostrense', 'AN', 60030, NULL),
(1579, 'Bolognola', 'MC', 62035, NULL),
(1580, 'Borgo Pace', 'PU', 61040, NULL),
(1581, 'Cagli', 'PU', 61043, NULL),
(1582, 'Caldarola', 'MC', 62020, NULL),
(1583, 'Camerano', 'AN', 60021, NULL),
(1584, 'Camerata Picena', 'AN', 60020, NULL),
(1585, 'Camerino', 'MC', 62032, NULL),
(1586, 'Campofilone', 'FM', 63828, NULL),
(1587, 'Camporotondo di Fiastrone', 'MC', 62020, NULL),
(1588, 'Cantiano', 'PU', 61044, NULL),
(1589, 'Carassai', 'AP', 63063, NULL),
(1590, 'Carpegna', 'PU', 61021, NULL),
(1591, 'Cartoceto', 'PU', 61030, NULL),
(1592, 'Castel Colonna', 'AN', 60010, NULL),
(1593, 'Castel di Lama', 'AP', 63082, NULL),
(1594, 'Castelbellino', 'AN', 60030, NULL),
(1595, 'Castelfidardo', 'AN', 60022, NULL),
(1596, 'Castelleone di Suasa', 'AN', 60010, NULL),
(1597, 'Castelplanio', 'AN', 60031, NULL),
(1598, 'Castelraimondo', 'MC', 62022, NULL),
(1599, 'Castelsantangelo sul Nera', 'MC', 62039, NULL),
(1600, 'Castignano', 'AP', 63072, NULL),
(1601, 'Castorano', 'AP', 63081, NULL),
(1602, 'Cerreto d\'Esi', 'AN', 60043, NULL),
(1603, 'Cessapalombo', 'MC', 62020, NULL),
(1604, 'Chiaravalle', 'AN', 60033, NULL),
(1605, 'Civitanova Marche', 'MC', 62012, NULL),
(1606, 'Colbordolo', 'PU', 61022, NULL),
(1607, 'Colli del Tronto', 'AP', 63079, NULL),
(1608, 'Colmurano', 'MC', 62020, NULL),
(1609, 'Comunanza', 'AP', 63087, NULL),
(1610, 'Corinaldo', 'AN', 60013, NULL),
(1611, 'Corridonia', 'MC', 62014, NULL),
(1612, 'Cossignano', 'AP', 63067, NULL),
(1613, 'Cupra Marittima', 'AP', 63064, NULL),
(1614, 'Cupramontana', 'AN', 60034, NULL),
(1615, 'Esanatoglia', 'MC', 62024, NULL),
(1616, 'Fabriano', 'AN', 60044, NULL),
(1617, 'Falconara Marittima', 'AN', 60015, NULL),
(1618, 'Falerone', 'FM', 63837, NULL),
(1619, 'Fano', 'PU', 61032, NULL),
(1620, 'Fermignano', 'PU', 61033, NULL),
(1621, 'Fermo', 'FM', 63900, NULL),
(1622, 'Fiastra', 'MC', 62035, NULL),
(1623, 'Filottrano', 'AN', 60024, NULL),
(1624, 'Fiordimonte', 'MC', 62035, NULL),
(1625, 'Fiuminata', 'MC', 62025, NULL),
(1626, 'Folignano', 'AP', 63084, NULL),
(1627, 'Force', 'AP', 63086, NULL),
(1628, 'Fossombrone', 'PU', 61034, NULL),
(1629, 'Francavilla d\'Ete', 'FM', 63816, NULL),
(1630, 'Fratte Rosa', 'PU', 61040, NULL),
(1631, 'Frontino', 'PU', 61021, NULL),
(1632, 'Frontone', 'PU', 61040, NULL),
(1633, 'Gabicce Mare', 'PU', 61011, NULL),
(1634, 'Gagliole', 'MC', 62022, NULL),
(1635, 'Genga', 'AN', 60040, NULL),
(1636, 'Gradara', 'PU', 61012, NULL),
(1637, 'Grottammare', 'AP', 63066, NULL),
(1638, 'Grottazzolina', 'FM', 63844, NULL),
(1639, 'Gualdo', 'MC', 62020, NULL),
(1640, 'Isola del Piano', 'PU', 61030, NULL),
(1641, 'Lapedona', 'FM', 63823, NULL),
(1642, 'Loreto', 'AN', 60025, NULL),
(1643, 'Loro Piceno', 'MC', 62020, NULL),
(1644, 'Lunano', 'PU', 61026, NULL),
(1645, 'Macerata', 'MC', 62100, NULL),
(1646, 'Macerata Feltria', 'PU', 61023, NULL),
(1647, 'Magliano di Tenna', 'FM', 63832, NULL),
(1648, 'Maiolati Spontini', 'AN', 60030, NULL),
(1649, 'Maltignano', 'AP', 63085, NULL),
(1650, 'Massa Fermana', 'FM', 63834, NULL),
(1651, 'Massignano', 'AP', 63061, NULL),
(1652, 'Matelica', 'MC', 62024, NULL),
(1653, 'Mercatello sul Metauro', 'PU', 61040, NULL),
(1654, 'Mercatino Conca', 'PU', 61013, NULL),
(1655, 'Mergo', 'AN', 60030, NULL),
(1656, 'Mogliano', 'MC', 62010, NULL),
(1657, 'Mombaroccio', 'PU', 61024, NULL),
(1658, 'Mondavio', 'PU', 61040, NULL),
(1659, 'Mondolfo', 'PU', 61037, NULL),
(1660, 'Monsampietro Morico', 'FM', 63842, NULL),
(1661, 'Monsampolo del Tronto', 'AP', 63077, NULL),
(1662, 'Monsano', 'AN', 60030, NULL),
(1663, 'Montalto delle Marche', 'AP', 63068, NULL),
(1664, 'Montappone', 'FM', 63835, NULL),
(1665, 'Monte Cavallo', 'MC', 62036, NULL),
(1666, 'Monte Cerignone', 'PU', 61010, NULL),
(1667, 'Monte Giberto', 'FM', 63846, NULL),
(1668, 'Monte Grimano Terme', 'PU', 61010, NULL),
(1669, 'Monte Porzio', 'PU', 61040, NULL),
(1670, 'Monte Rinaldo', 'FM', 63852, NULL),
(1671, 'Monte Roberto', 'AN', 60030, NULL),
(1672, 'Monte San Giusto', 'MC', 62015, NULL),
(1673, 'Monte San Martino', 'MC', 62020, NULL),
(1674, 'Monte San Pietrangeli', 'FM', 63815, NULL),
(1675, 'Monte San Vito', 'AN', 60037, NULL),
(1676, 'Monte Urano', 'FM', 63813, NULL),
(1677, 'Monte Vidon Combatte', 'FM', 63847, NULL),
(1678, 'Monte Vidon Corrado', 'FM', 63836, NULL),
(1679, 'Montecalvo in Foglia', 'PU', 61020, NULL),
(1680, 'Montecarotto', 'AN', 60036, NULL),
(1681, 'Montecassiano', 'MC', 62010, NULL),
(1682, 'Monteciccardo', 'PU', 61024, NULL),
(1683, 'Montecopiolo', 'PU', 61014, NULL),
(1684, 'Montecosaro', 'MC', 62010, NULL),
(1685, 'Montedinove', 'AP', 63069, NULL),
(1686, 'Montefalcone Appennino', 'FM', 63855, NULL),
(1687, 'Montefano', 'MC', 62010, NULL),
(1688, 'Montefelcino', 'PU', 61030, NULL),
(1689, 'Montefiore dell\'Aso', 'AP', 63062, NULL),
(1690, 'Montefortino', 'FM', 63858, NULL),
(1691, 'Montegallo', 'AP', 63094, NULL),
(1692, 'Montegiorgio', 'FM', 63833, NULL),
(1693, 'Montegranaro', 'FM', 63812, NULL),
(1694, 'Montelabbate', 'PU', 61025, NULL),
(1695, 'Monteleone di Fermo', 'FM', 63841, NULL),
(1696, 'Montelparo', 'FM', 63853, NULL),
(1697, 'Montelupone', 'MC', 62010, NULL),
(1698, 'Montemaggiore al Metauro', 'PU', 61030, NULL),
(1699, 'Montemarciano', 'AN', 60018, NULL),
(1700, 'Montemonaco', 'AP', 63088, NULL),
(1701, 'Monteprandone', 'AP', 63076, NULL),
(1702, 'Monterado', 'AN', 60010, NULL),
(1703, 'Monterubbiano', 'FM', 63825, NULL),
(1704, 'Montottone', 'FM', 63843, NULL),
(1705, 'Moresco', 'FM', 63826, NULL),
(1706, 'Morro d\'Alba', 'AN', 60030, NULL),
(1707, 'Morrovalle', 'MC', 62010, NULL),
(1708, 'Muccia', 'MC', 62034, NULL),
(1709, 'Numana', 'AN', 60026, NULL),
(1710, 'Offagna', 'AN', 60020, NULL),
(1711, 'Offida', 'AP', 63073, NULL),
(1712, 'Orciano di Pesaro', 'PU', 61038, NULL),
(1713, 'Ortezzano', 'FM', 63851, NULL),
(1714, 'Osimo', 'AN', 60027, NULL),
(1715, 'Ostra', 'AN', 60010, NULL),
(1716, 'Ostra Vetere', 'AN', 60010, NULL),
(1717, 'Palmiano', 'AP', 63092, NULL),
(1718, 'Pedaso', 'FM', 63827, NULL),
(1719, 'Peglio', 'PU', 61049, NULL),
(1720, 'Penna San Giovanni', 'MC', 62020, NULL),
(1721, 'Pergola', 'PU', 61045, NULL),
(1722, 'Pesaro', 'PU', 6112, NULL),
(1723, 'Petriano', 'PU', 61020, NULL),
(1724, 'Petriolo', 'MC', 62014, NULL),
(1725, 'Petritoli', 'FM', 63848, NULL),
(1726, 'Piagge', 'PU', 61030, NULL),
(1727, 'Piandimeleto', 'PU', 61026, NULL),
(1728, 'Pietrarubbia', 'PU', 61023, NULL),
(1729, 'Pieve Torina', 'MC', 62036, NULL),
(1730, 'Pievebovigliana', 'MC', 62035, NULL),
(1731, 'Piobbico', 'PU', 61046, NULL),
(1732, 'Pioraco', 'MC', 62025, NULL),
(1733, 'Poggio San Marcello', 'AN', 60030, NULL),
(1734, 'Poggio San Vicino', 'MC', 62021, NULL),
(1735, 'Pollenza', 'MC', 62010, NULL),
(1736, 'Polverigi', 'AN', 60020, NULL),
(1737, 'Ponzano di Fermo', 'FM', 63845, NULL),
(1738, 'Porto Recanati', 'MC', 62017, NULL),
(1739, 'Porto San Giorgio', 'FM', 63822, NULL),
(1740, 'Porto Sant\'Elpidio', 'FM', 63821, NULL),
(1741, 'Potenza Picena', 'MC', 62018, NULL),
(1742, 'Rapagnano', 'FM', 63831, NULL),
(1743, 'Recanati', 'MC', 62019, NULL),
(1744, 'Ripatransone', 'AP', 63065, NULL),
(1745, 'Ripe', 'AN', 60010, NULL),
(1746, 'Ripe San Ginesio', 'MC', 62020, NULL),
(1747, 'Roccafluvione', 'AP', 63093, NULL),
(1748, 'Rosora', 'AN', 60030, NULL),
(1749, 'Rotella', 'AP', 63071, NULL),
(1750, 'Saltara', 'PU', 61030, NULL),
(1751, 'San Benedetto del Tronto', 'AP', 63074, NULL),
(1752, 'San Costanzo', 'PU', 61039, NULL),
(1753, 'San Ginesio', 'MC', 62026, NULL),
(1754, 'San Giorgio di Pesaro', 'PU', 61030, NULL),
(1755, 'San Lorenzo in Campo', 'PU', 61047, NULL),
(1756, 'San Marcello', 'AN', 60030, NULL),
(1757, 'San Paolo di Jesi', 'AN', 60038, NULL),
(1758, 'San Severino Marche', 'MC', 62027, NULL),
(1759, 'Santa Maria Nuova', 'AN', 60030, NULL),
(1760, 'Santa Vittoria in Matenano', 'FM', 63854, NULL),
(1761, 'Sant\'Angelo in Lizzola', 'PU', 61020, NULL),
(1762, 'Sant\'Angelo in Pontano', 'MC', 62020, NULL),
(1763, 'Sant\'Angelo in Vado', 'PU', 61048, NULL),
(1764, 'Sant\'Elpidio a Mare', 'FM', 63811, NULL),
(1765, 'Sant\'Ippolito', 'PU', 61040, NULL),
(1766, 'Sarnano', 'MC', 62028, NULL),
(1767, 'Sassocorvaro', 'PU', 61028, NULL),
(1768, 'Sassofeltrio', 'PU', 61013, NULL),
(1769, 'Sassoferrato', 'AN', 60041, NULL),
(1770, 'Sefro', 'MC', 62025, NULL),
(1771, 'Senigallia', 'AN', 60019, NULL),
(1772, 'Serra de\' Conti', 'AN', 60030, NULL),
(1773, 'Serra San Quirico', 'AN', 60048, NULL),
(1774, 'Serra Sant\'Abbondio', 'PU', 61040, NULL),
(1775, 'Serrapetrona', 'MC', 62020, NULL),
(1776, 'Serravalle di Chienti', 'MC', 62038, NULL),
(1777, 'Serrungarina', 'PU', 61030, NULL),
(1778, 'Servigliano', 'FM', 63839, NULL),
(1779, 'Sirolo', 'AN', 60020, NULL),
(1780, 'Smerillo', 'FM', 63856, NULL),
(1781, 'Spinetoli', 'AP', 63078, NULL),
(1782, 'Staffolo', 'AN', 60039, NULL),
(1783, 'Tavoleto', 'PU', 61020, NULL),
(1784, 'Tavullia', 'PU', 61010, NULL),
(1785, 'Tolentino', 'MC', 62029, NULL),
(1786, 'Torre San Patrizio', 'FM', 63814, NULL),
(1787, 'Treia', 'MC', 62010, NULL),
(1788, 'Urbania', 'PU', 61049, NULL),
(1789, 'Urbino', 'PU', 61029, NULL),
(1790, 'Urbisaglia', 'MC', 62010, NULL),
(1791, 'Ussita', 'MC', 62039, NULL),
(1792, 'Venarotta', 'AP', 63091, NULL),
(1793, 'Visso', 'MC', 62039, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `Eventi`
--

CREATE TABLE `Eventi` (
  `ID_evento` int(11) NOT NULL,
  `titolo` varchar(50) NOT NULL,
  `descrizione` text NOT NULL,
  `target` enum('TERRITORIALE','SCOLASTICO') DEFAULT NULL,
  `ora_inizio` datetime NOT NULL,
  `ora_fine` datetime DEFAULT NULL,
  `visibile` tinyint(1) NOT NULL,
  `prenotabile` tinyint(1) NOT NULL,
  `via_P` varchar(50) DEFAULT NULL,
  `n_civico_P` varchar(11) DEFAULT NULL,
  `coordinate` point DEFAULT NULL,
  `descrizione_breve` varchar(100) NOT NULL,
  `data_eliminazione` datetime DEFAULT NULL,
  `id_citta` int(11) DEFAULT NULL,
  `cod_scuola` varchar(10) DEFAULT NULL,
  `id_foto` int(11) DEFAULT 54
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Trigger `Eventi`
--
DELIMITER $$
CREATE TRIGGER `default_target` BEFORE INSERT ON `Eventi` FOR EACH ROW BEGIN
    IF NEW.cod_scuola IS NULL THEN
        SET NEW.target = 'TERRITORIALE';
    ELSE
        SET NEW.target = 'SCOLASTICO';
        SET NEW.via_P = NULL;
        SET NEW.n_civico_P = NULL;
        SET NEW.id_citta = NULL;
        SET NEW.coordinate = NULL;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `Foto`
--

CREATE TABLE `Foto` (
  `ID_foto` int(11) NOT NULL,
  `path_foto` varchar(500) NOT NULL,
  `data_eliminazione` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Foto`
--

INSERT INTO `Foto` (`ID_foto`, `path_foto`, `data_eliminazione`) VALUES
(1, 'https://3elleorienta.sviluppo.host/pictures/eventi/ev1.png', NULL),
(2, 'https://3elleorienta.sviluppo.host/pictures/eventi/ev2.jpg', NULL),
(3, 'https://3elleorienta.sviluppo.host/pictures/progetti/prg1.jpg', NULL),
(4, 'https://3elleorienta.sviluppo.host/pictures/progetti/prg2.jpg', NULL),
(6, 'https://3elleorienta.sviluppo.host/pictures/scuole/LICEO_SCIENTIFICO_LEONARDO_DA_VINCI/sc3.jpg', NULL),
(8, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_CUPPARI_SALVATI/images.png', NULL),
(9, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_CUPPARI_SALVATI/salvati_viale_accesso_G.jpg', NULL),
(10, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_CUPPARI_SALVATI/testataCuppariSalvati.png', NULL),
(12, 'https://3elleorienta.sviluppo.host/pictures/606d611230a4adc2.jpg', NULL),
(13, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_GALILEI%20/channels4_profile.jpg', NULL),
(15, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_GALILEI%20/IIS-GALILEI_Shooting-Aprile-2025-HI-250.jpg', NULL),
(16, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_GALILEI%20/istituto-Galilei.jpg', NULL),
(17, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_MARCONI_PIERALISI/download%20%281%29.png', NULL),
(18, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_MARCONI_PIERALISI/download2.jpg', NULL),
(19, 'https://3elleorienta.sviluppo.host/pictures/scuole/ISTITUTO_ALBERGHIERO_IPSEOA%20_Varnelli/images.jpg', NULL),
(20, 'https://3elleorienta.sviluppo.host/pictures/scuole/ISTITUTO_ALBERGHIERO_IPSEOA%20_Varnelli/logo.png', NULL),
(21, 'https://3elleorienta.sviluppo.host/pictures/scuole/LICEO_ARTISTICO_Edgardo_Mannucci/download.jpg', NULL),
(22, 'https://3elleorienta.sviluppo.host/pictures/scuole/LICEO_ARTISTICO_Edgardo_Mannucci/hq720.jpg', NULL),
(23, 'https://3elleorienta.sviluppo.host/pictures/scuole/LICEO_CLASSICO_VITTORIO_EMANUELE_II/foto%202.jpg', NULL),
(24, 'https://3elleorienta.sviluppo.host/pictures/scuole/LICEO_CLASSICO_VITTORIO_EMANUELE_II/images%20%281%29.jpg', NULL),
(25, 'https://3elleorienta.sviluppo.host/pictures/scuole/LICEO_CLASSICO_VITTORIO_EMANUELE_II/sc2.jpg', NULL),
(26, 'https://3elleorienta.sviluppo.host/pictures/scuole/IIS_MARCONI_PIERALISI/sc1.jpg', NULL),
(27, 'https://3elleorienta.sviluppo.host/pictures/links/logo-slide-sorprendo.png', NULL),
(28, 'https://3elleorienta.sviluppo.host/pictures/links/maxresdefault.jpg', NULL),
(29, 'https://3elleorienta.sviluppo.host/pictures/links/scelta-superiori-large.png', NULL),
(30, 'https://3elleorienta.sviluppo.host/pictures/links/scuola-attiva-procedura-registrazione-iscrizioni-online.jpg', NULL),
(31, 'https://3elleorienta.sviluppo.host/pictures/links/scuola-in-chiaro.png', NULL),
(34, 'https://3elleorienta.sviluppo.host/pictures/home/logo_un_po_piu_grande.jpg', NULL),
(54, 'https://3elleorienta.sviluppo.host/pictures/nofoto.jpg', NULL),
(86, 'https://3elleorienta.sviluppo.host/pictures/upload/2c69a47a0e13cbe4.jpg', NULL),
(90, 'https://3elleorienta.sviluppo.host/pictures/upload/ed5de1a482e4deab.jpg', NULL),
(91, 'https://3elleorienta.sviluppo.host/pictures/upload/8125579f48436ebf.jpg', NULL);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetAmbiti`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetAmbiti` (
`ID_ambito` int(11)
,`nome` varchar(50)
,`descrizione` mediumtext
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetCitta`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetCitta` (
`ID_citta` int(11)
,`nome` varchar(50)
,`sigla_provincia` varchar(2)
,`cap` int(11)
,`id_zona` int(11)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetEventiAttivi`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetEventiAttivi` (
`ID_evento` int(11)
,`titolo` varchar(50)
,`descrizione` text
,`target` enum('TERRITORIALE','SCOLASTICO')
,`ora_inizio` datetime
,`ora_fine` datetime
,`visibile` tinyint(1)
,`prenotabile` tinyint(1)
,`via_P` varchar(50)
,`n_civico_P` varchar(11)
,`coordinate` point
,`descrizione_breve` varchar(100)
,`id_citta` int(11)
,`nome_citta` varchar(50)
,`sigla_provincia` varchar(2)
,`cod_scuola` varchar(10)
,`nome_scuola` varchar(50)
,`id_foto` int(11)
,`path_foto` varchar(500)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetFoto`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetFoto` (
`ID_foto` int(11)
,`path_foto` varchar(500)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetIndirizziStudio`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetIndirizziStudio` (
`ID_indirizzo` int(11)
,`nome` varchar(200)
,`ordine` int(11)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetLinks`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetLinks` (
`ID_link` int(11)
,`titolo` varchar(50)
,`url_link` varchar(100)
,`descrizione` text
,`n_ordine` int(11)
,`id_foto` int(11)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetProgetti`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetProgetti` (
`ID_progetto` int(11)
,`titolo` varchar(70)
,`descrizione` text
,`n_ordine` int(11)
,`id_foto` int(11)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetProvince`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetProvince` (
`sigla` varchar(2)
,`nome` varchar(70)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetScuole`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetScuole` (
`COD_meccanografico` varchar(10)
,`nome` varchar(50)
,`descrizione` text
,`sito` varchar(100)
,`via` varchar(30)
,`n_civico` varchar(11)
,`id_citta` int(11)
,`coordinate` point
,`id_foto` int(11)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetScuoleAmbiti`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetScuoleAmbiti` (
`cod_scuola` varchar(10)
,`id_ambito` int(11)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetScuoleIndirizzi`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetScuoleIndirizzi` (
`cod_scuola` varchar(10)
,`id_indirizzo` int(11)
,`n_ordine` int(11)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetUtenti`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetUtenti` (
`ID_utente` int(11)
,`username` varchar(32)
,`email` varchar(254)
,`tipo` enum('ADMIN','SCOLASTICO')
,`stato` enum('ATTIVO','BLOCCATO')
,`cod_scuola` varchar(10)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `GetZone`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `GetZone` (
`ID_zona` int(11)
,`nome` varchar(30)
);

-- --------------------------------------------------------

--
-- Struttura della tabella `Indirizzi_studio`
--

CREATE TABLE `Indirizzi_studio` (
  `ID_indirizzo` int(11) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `ordine` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Indirizzi_studio`
--

INSERT INTO `Indirizzi_studio` (`ID_indirizzo`, `nome`, `ordine`) VALUES
(1, 'Enogastronomia-cucina', 1),
(2, 'Enogastronomia- Servizi di Sala e vendita', 2),
(3, 'Produzioni dolciarie artigianali e industriali', 3),
(4, 'Accoglienza turistica', 4),
(5, 'Accoglienza turistica, turismo dello sport del tempo libero e del benessere', 5),
(6, 'Enogastronomia 4.0: Scienza e Qualità', 6),
(7, 'Food and beverage management per la sostenibilità e l\'innovazione', 7),
(8, 'Arti figurative', 8),
(9, 'Design del Gioiello', 10),
(10, 'Amministrazione, Finanza e Marketing', 11),
(11, 'Sistemi Informativi Aziendali', 12),
(12, 'Turismo', 13),
(13, 'Costruzioni, Ambiente E Territorio', 14),
(14, 'Agricoltura, Sviluppo Rurale, Valorizzazione Dei Prodotti Del Territorio E Gestione Delle Risorse Forestali E Montane', 15),
(15, 'Biotecnologie Ambientali', 16),
(16, 'Biotecnologie Sanitarie', 17),
(17, 'Biotecnologie Sanitarie e della Nutrizione', 18),
(18, 'Liceo delle Scienze Umane indirizzo Economico Sociale', 19),
(19, 'Informatica - Istruzione Tecnica', 20),
(20, 'Elettronica - Istruzione Tecnica', 21),
(21, 'Automazione - Istruzione Tecnica', 22),
(22, 'Meccatronica - Istruzione Tecnica', 23),
(23, 'Manutenzione e Assistenza Tecnica - Istruzione Professionale', 24),
(24, 'Moda - Istruzione Professionale', 25),
(25, 'Liceo Classico', 26),
(26, 'Liceo delle scienze umane', 27),
(27, 'Liceo delle scienze umane opzione economico sociale', 28),
(28, 'Liceo Scientifico', 29),
(29, 'Liceo Scientifico opzione Scienze Applicate', 30),
(30, 'Liceo Scientifico indirizzo Sportivo', 31),
(31, 'Liceo Linguistico', 32);

-- --------------------------------------------------------

--
-- Struttura della tabella `Links`
--

CREATE TABLE `Links` (
  `ID_link` int(11) NOT NULL,
  `titolo` varchar(50) NOT NULL,
  `url_link` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `descrizione` text NOT NULL,
  `n_ordine` int(11) NOT NULL,
  `data_eliminazione` date DEFAULT NULL,
  `id_foto` int(11) NOT NULL DEFAULT 54
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Links`
--

INSERT INTO `Links` (`ID_link`, `titolo`, `url_link`, `icon`, `descrizione`, `n_ordine`, `data_eliminazione`, `id_foto`) VALUES
(1, 'SORPRENDO', 'https://www.sorprendo.it/', NULL, 'Una piattaforma online di orientamento che aiuta gli studenti a scegliere il proprio futuro tramite test di interessi, analisi delle competenze e informazioni su oltre 450 carriere e percorsi di studio. Permette anche di creare un piano personalizzato.', 1, NULL, 27),
(2, 'Piattaforma UNICA', 'https://unica.istruzione.gov.it/sic', NULL, 'Una piattaforma digitale che riunisce in un unico spazio i servizi per studenti e famiglie: orientamento, comunicazioni scuola-famiglia, iscrizioni e informazioni sul percorso scolastico. Pensata per semplificare la gestione della vita scolastica.', 2, NULL, 31),
(4, 'Iscrizioni Online', 'https://www.istruzione.it/iscrizionionline/', NULL, 'Il portale ufficiale che permette alle famiglie di iscrivere online gli studenti al primo anno di scuola (primaria, medie e superiori). \r\nIl servizio gratuito e richiede l\' accesso con identita\' digitale come SPID o CIE.', 3, NULL, 30),
(5, 'Scegliere il percorso di scuola superiore', 'https://www.mim.gov.it/web/guest/scegliere-il-percorso-di-scuola-superiore', NULL, 'Questa pagina aiuta a scegliere la scuola superiore illustrando le differenze tra licei, istituti tecnici, professionali e percorsi di formazione professionale. \r\nSpiega le opportunita\' e gli indirizzi disponibili per orientare gli studenti verso il percorso piu\' adatto.', 4, NULL, 29),
(6, 'Scuola secondaria di secondo grado', 'https://www.mim.gov.it/web/guest/scuola-secondaria-di-secondo-grado', NULL, 'Il sito spiega l\' organizzazione delle scuole superiori in Italia: licei, istituti tecnici e professionali, con i vari indirizzi disponibili e le caratteristiche di ogni percorso. \r\nDescrive anche durata (5 anni) e obiettivi formativi, come preparare gli studenti all\'universita\' o al lavoro.', 5, NULL, 28),
(22, 'portale1', 'htts://www.google.com/', NULL, 'portale1', 6, '2026-06-06', 91);

-- --------------------------------------------------------

--
-- Struttura della tabella `Progetti`
--

CREATE TABLE `Progetti` (
  `ID_progetto` int(11) NOT NULL,
  `titolo` varchar(70) NOT NULL,
  `descrizione` text NOT NULL,
  `n_ordine` int(11) NOT NULL,
  `data_eliminazione` date DEFAULT NULL,
  `id_foto` int(11) NOT NULL DEFAULT 54
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `Progetti`
--

INSERT INTO `Progetti` (`ID_progetto`, `titolo`, `descrizione`, `n_ordine`, `data_eliminazione`, `id_foto`) VALUES
(1, '3L LifeLongLearning.it', '3L LifeLongLearning.it è un progetto di orientamento a cui partecipano 11 scuole di primo grado e 9 di secondo grado dei territori di Jesi e di Fabriano.\r\n\r\nIl progetto, presentato dall’IIS Cuppari Salvati, è finanziato dalla Regione Marche ed ha lo scopo di mettere a sistema l’offerta di orientamento con azioni di carattere informativo, formativo e consulenziale rivolte agli studenti di scuola secondaria di primo grado e dei primi due anni dei percorsi del secondo ciclo di istruzione.\r\n\r\nL’idea progettuale coinvolge docenti orientatori, studenti e famiglie di un vasto territorio e rafforza le azioni di orientamento messe in atto da ogni singola scuola partner grazie ad una più ampia innovazione digitale e ad alternative Unità Didattiche di Apprendimento (UDA) la cui finalità è la valorizzazione del processo formativo rispetto a quello informativo e la progettazione condivisa favorirà, a livello individuale, la scoperta di interessi ed attitudini.\r\n\r\n3L LifeLongLearning.it propone di:\r\n\r\npotenziare Unità Didattiche di Apprendimento (UDA) corrispondenti a specifici ambiti di interesse;\r\nprodurre e condividere con studenti e famiglie azioni informative e formative;\r\navviare percorsi di ricerca-azione sperimentali di didattica orientativa;\r\nattivare percorsi di consulenza orientativa per studenti e famiglie;\r\ncostruire la piattaforma 3elleorienta.it per favorire, in modalità sincrona ed asincrona, l’orientamento formativo d informativo e condividere azioni e materiali pertinenti;\r\nrealizzare e mettere a disposizione degli studenti un’APP su Smartphone per avere informazioni sulle attività orientative;\r\nfar conoscere la piattaforma Sorprendo per aiutare gli studenti a prendere decisioni e a realizzare il proprio personale percorso di orientamento;\r\nmonitorare e valutare l’efficacia ed efficienza degli obbiettivi raggiunti.\r\nConsiderato poi che l’azione orientativa non deve essere isolata, circoscritta solo ed esclusivamente al momento di “passaggio tra un ordine di scuola e l’altro” ma deve accompagnare costantemente la vita di ogni individuo il progetto 3L LifeLongLearning.it intende consentire allo studente e successivamente anche all’adulto di:\r\n\r\nvalutare consapevolmente un percorso di istruzione e formazione per il quale il singolo studente riconosce di avere attitudine e interesse tra la gamma di tutti quelli proposti nel territorio;\r\nvalutare in modo autonomo e critico il livello qualitativo di una proposta formativa finalizzata all’acquisizione di competenze scegliendo quelle maggiormente efficaci;\r\nvalutare in modo responsabile il contesto territoriale, professionale e culturale di riferimento cercando di comprendere il proprio ruolo.\r\nASSE III P.Inv 10.4 – D.D.P.F. n. 1050/IFD del 26/06/2019.\r\nFinanziato dalla Regione Marche con D.D.P.F. n. 71/IFD del 30/01/2020\r\nCod. SIFORM 1015864\r\n\r\n\r\nhttps://www.google.com/?hl=it', 1, NULL, 86),
(2, 'Reti Territoriali per l’Orientamento', 'Il progetto “Reti Territoriali per l’Orientamento” della Regione Marche ha l’obiettivo primario di migliorare l’orientamento scolastico e professionale degli studenti, creando sinergie tra le diverse scuole del territorio. Quest’iniziativa mira a supportare gli studenti nella transizione tra i vari gradi di istruzione, promuovendo percorsi formativi che rispettino le inclinazioni, gli interessi e le potenzialità degli alunni.\r\nObiettivi principali del progetto:\r\n\r\nCreazione di una rete di scuole per il miglioramento dell’orientamento.\r\nOrganizzazione di eventi di orientamento e incontri tra studenti ed ex studenti per condividere esperienze.\r\nSviluppo di attività formative e informative attraverso il coinvolgimento di esperti.\r\nPromozione della cooperazione tra scuole, famiglie e istituzioni.\r\nScuole partecipanti alla rete:\r\n\r\nI.C. Bartolini – Cupramontana\r\nI.C. Carlo Urbani – JESI\r\nI.C. Carlo Urbani – Moie di Maiolati\r\nI.C. Federico II – JESI\r\nI.C. Lorenzo Lotto – JESI\r\nI.C. Rita Levi Montalcini – Chiaravalle\r\nI.C. San Francesco – JESI\r\nIIS Cuppari Salvati – Jesi\r\nIIS Galileo Galilei – Jesi (Capofila)\r\nIIS Marconi Pieralisi – JESI\r\nLiceo Classico V. Emanuele II – JESI\r\nLiceo Scientifico L. Da Vinci – JESI\r\nIl Capofila del progetto, l’IIS Galileo Galilei di Jesi, coordina le attività formative e le iniziative di orientamento per garantire un processo educativo efficiente e in grado di rispondere alle esigenze del territorio.\r\n\r\nProgetto cod. SIFORM 1095939 denominazione: “Svelati” – codice bando siform2 ORIENTAMENTO CONTINUO 2024 – DGR 1591 del 06/11/2023 – PR FSE+ 2021/2027 Asse 2 Istruzione e Formazione OS 4.e (4) – Orientamento continuo Campo di intervento 149 Avviso pubblico relativo alla presentazione di progetti di “Reti territoriali per l’orientamento” – approvato con DDS n. 336 del 15/12/2023. CUP: C41I24000070002', 2, NULL, 4),
(29, 'Dai che ci siete quasi!', 'Un grande in bocca al lupo dalla prof.ssa!', 1, NULL, 54),
(31, 'prova', 'daje', -6, '2026-06-06', 90);

-- --------------------------------------------------------

--
-- Struttura della tabella `Province`
--

CREATE TABLE `Province` (
  `sigla` varchar(2) NOT NULL,
  `nome` varchar(70) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Province`
--

INSERT INTO `Province` (`sigla`, `nome`) VALUES
('AN', 'Ancona'),
('AP', 'Ascoli Piceno'),
('FM', 'Fermo'),
('MC', 'Macerata'),
('PU', 'Pesaro e Urbino');

-- --------------------------------------------------------

--
-- Struttura della tabella `Scuole`
--

CREATE TABLE `Scuole` (
  `COD_meccanografico` varchar(10) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descrizione` text NOT NULL,
  `sito` varchar(100) NOT NULL,
  `via` varchar(30) NOT NULL,
  `n_civico` varchar(11) NOT NULL,
  `id_citta` int(11) NOT NULL,
  `coordinate` point NOT NULL,
  `id_foto` int(11) NOT NULL DEFAULT 54,
  `data_eliminazione` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Scuole`
--

INSERT INTO `Scuole` (`COD_meccanografico`, `nome`, `descrizione`, `sito`, `via`, `n_civico`, `id_citta`, `coordinate`, `id_foto`, `data_eliminazione`) VALUES
('ANIS023002', 'IIS \"MARCONI PIERALISI\"', 'Il “Marconi Pieralisi” nasce nell’a.s. 2018-19 dall’unione di due Istituti ben integrati nella tradizione lavorativa del territorio jesino e della media Vallesina: l’ITIS “Guglielmo Marconi” e l’IPSIA “Egisto Pieralisi”. \r\n\r\nIl dialogo costante con le Aziende e le numerose occasioni di collaborazione consentono di adeguare la formazione tecnico-professionale alle reali necessità industriali e manifatturiere, facilitando l’inserimento degli alunni nel mondo del lavoro.\r\n\r\nSito scuola https://www.iismarconipieralisi.it/\r\n\r\nVideo di presentazione Indirizzi\r\n\r\nInformatica https://youtu.be/77W_WRkAIgA\r\n\r\nElettronica e Automazione https://youtu.be/2s_d5H2jzUM\r\n\r\nMeccatronica https://youtu.be/6Tyt4Ff1EUM\r\n\r\nModa https://youtu.be/mTOl5Gt9yu8\r\n\r\nManutenzione e Assistenza Tecnica https://youtu.be/HgtkLSlN2oI\r\n\r\n\r\nDepliant informativo https://www.iismarconipieralisi.edu.it/images/docs/brochure/iis_marconipieralisi-jesi_depliant-indirizzi_10-2025.pdf', 'https://www.iismarconipieralisi.edu.it/', 'Via Fioretti', '3', 1, 0x00000000010100000089e4750eaf752a40b65db23beec24540, 26, NULL),
('ANPC060007', 'LICEO CLASSICO “Vittorio Emanuele II”', 'Il LICEO VITTORIO EMANUELE II che vanta una prestigiosa ed antica tradizione , ospita presso il palazzo storico di Corso Matteotti tre corsi di studio coniugando l’antico con il moderno . All’indirizzo classico si affiancano quello di Scienze umane e Economico sociale , nella convinzione che la “cultura guarda al futuro “ .\r\n\r\nVideo:\r\nhttps://youtu.be/-IMNRDxBBd8?si=lBvaC7xi7EW_ZGVp\r\n\r\nSito:\r\nhttps://liceoclassicojesi.edu.it/', 'https://liceoclassicojesi.edu.it/', 'Corso Matteotti', '48', 1, 0x000000000101000000b72407ec6a7a2a40850a0e2f88c24540, 25, NULL),
('ANPS040005', 'LICEO SCIENTIFICO \"Leonardo Da Vinci\"', 'Tutti gli studenti dei quattro corsi del Liceo “Leonardo da Vinci” sono ospitati in un’unica struttura. La finalità educativa del Liceo è quella della formazione unitaria della persona e del cittadino in modo che, sviluppando le capacità critiche indispensabili per leggere e interpretare la realtà in modo autonomo e consapevole, egli sappia entrare in una relazione costruttiva e serena con sé, con gli altri e con il mondo. La formazione liceale si caratterizza per l\'apertura ai diversi saperi e mira ad integrare le varie aree disciplinari, cercando uno stretto rapporto tra cultura umanistica e cultura scientifica. La lettura dei testi letterari, lo studio del pensiero filosofico e scientifico e l’attenzione al collegamento tra sapere teorico e pratiche laboratoriali costituiscono un patrimonio prezioso per chiunque voglia comprendere una realtà complessa ed interagire con essa.\r\n\r\nhttps://www.liceodavincijesi.edu.it/', 'https://www.liceodavincijesi.edu.it/', 'Viale VERDI', '23', 1, 0x00000000010100000039b35da10f762a4015c9570229c34540, 6, NULL),
('ANSD01001R', 'LICEO ARTISTICO \"EDGARDO MANNUCCI\"', 'Il liceo artistico sede di Jesi si caratterizza per un impianto generale del primo biennio in cui oltre alle materie comuni di tutti i licei, si sperimentano diverse discipline specifiche (laboratorio artistico , discipline grafico pittoriche, discipline plastiche , disegno geometrico). La storia dell’arte, disciplina fondamentale nel quinquennio crea le basi dei saperi pratici applicati nei laboratori. A partire dal terzo anno gli studenti sono chiamati a scegliere tra due indirizzi: Arti figurative e Design del Gioiello . Le competenze acquisite vengono implementate da collaborazioni con il territorio, in sinergia con Enti culturali privati e pubblici, aziende ed Associazioni.\r\n\r\nhttps://www.liceoartisticomannucci.edu.it/\r\n\r\nhttps://www.instagram.com/liceo.artistico.jesi/\r\n\r\nhttps://www.instagram.com/tv/CWoMti0jlqa/?igshid=YmMyMTA2M2Y%3D', 'https://www.liceoartisticomannucci.edu.it', 'Via Gallodoro', '77', 1, 0x000000000101000000c25087156e792a4076a565a4dec14540, 22, NULL),
('MCRH01000R', 'ISTITUTO ALBERGHIERO IPSEOA \"Varnelli\"', 'Nato nell’anno scolastico 1990/91 è l’unico della provincia di Macerata ed è l’unico ad essere situato in nzona montana, in uno dei Borghi più belli d’Italia, immerso nel verde e nella storia.\r\n\r\nQuesta sua peculiarità permette ai nostri studenti di vivere e formarsi in un contesto di straordinaria bellezza, in un ambiente scolastico sereno, tranquillo e protetto ma ricco degli stimoli necessari allo sviluppo della loro personalità e della loro professionalità.\r\n\r\nLa CURA che tutta la comunità mette in atto per accogliere i nuovi studenti è veramente un fiore all’occhiello della nostra scuola che è perfettamente integrata col proprio territorio, dove ognuno svolge la sua parte di educatore a tutti i livelli: le istituzioni comunali, le associazioni, le aziende del settore, i piccoli commercianti. \r\n\r\nUN HABITAT CULTURALE ED UMANO UNICO DOVE LO STUDENTE SI SENTE FELICE, ACCOLTO E STIMOLATO I numerosi successi ed i riconoscimenti internazionali ottenuti negli anni dagli studenti che si sono formati qui da noi sono una conferma dell’alto livello di preparazione, di innovazione e di formazione che l’Alberghiero G.Varnelli di Cingoli sa offrire. \r\n\r\nNon a caso gli iscritti ricevono offerte di lavoro già durante il corso di studi e la maggior parte dei diplomati trova occupazione stabile nel settore turistico – alberghiero.', 'https://www.ipseoavarnelli.edu.it/', 'Via Mazzini', '2', 2, 0x0000000001010000002ec55565df6d2a40231631ec30b04540, 20, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `Scuole_Ambiti`
--

CREATE TABLE `Scuole_Ambiti` (
  `cod_scuola` varchar(10) NOT NULL,
  `id_ambito` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Scuole_Ambiti`
--

INSERT INTO `Scuole_Ambiti` (`cod_scuola`, `id_ambito`) VALUES
('ANIS023002', 2),
('ANIS023002', 5),
('ANIS023002', 7),
('ANIS023002', 8),
('ANPC060007', 1),
('ANPC060007', 4),
('ANPC060007', 5),
('ANPC060007', 6),
('ANPS040005', 2),
('ANPS040005', 6),
('ANSD01001R', 5),
('ANSD01001R', 7),
('MCRH01000R', 3),
('MCRH01000R', 5),
('MCRH01000R', 10);

-- --------------------------------------------------------

--
-- Struttura della tabella `Scuole_Indirizzi`
--

CREATE TABLE `Scuole_Indirizzi` (
  `cod_scuola` varchar(10) NOT NULL,
  `id_indirizzo` int(11) NOT NULL,
  `n_ordine` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Scuole_Indirizzi`
--

INSERT INTO `Scuole_Indirizzi` (`cod_scuola`, `id_indirizzo`, `n_ordine`) VALUES
('ANIS023002', 19, 1),
('ANIS023002', 20, 2),
('ANIS023002', 21, 3),
('ANIS023002', 22, 4),
('ANIS023002', 23, 5),
('ANIS023002', 24, 6),
('ANPC060007', 25, 1),
('ANPC060007', 26, 2),
('ANPC060007', 27, 3),
('ANPS040005', 28, 1),
('ANPS040005', 29, 2),
('ANPS040005', 30, 3),
('ANPS040005', 31, 4),
('ANSD01001R', 8, 1),
('ANSD01001R', 9, 2),
('MCRH01000R', 1, 1),
('MCRH01000R', 2, 2),
('MCRH01000R', 3, 3),
('MCRH01000R', 4, 4),
('MCRH01000R', 5, 5),
('MCRH01000R', 6, 6),
('MCRH01000R', 7, 7);

-- --------------------------------------------------------

--
-- Struttura della tabella `Utenti`
--

CREATE TABLE `Utenti` (
  `ID_utente` int(11) NOT NULL,
  `username` varchar(32) NOT NULL,
  `hash_password` varchar(128) NOT NULL,
  `email` varchar(254) NOT NULL,
  `tipo` enum('ADMIN','SCOLASTICO') NOT NULL,
  `stato` enum('ATTIVO','BLOCCATO') NOT NULL,
  `cod_scuola` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `Utenti`
--

INSERT INTO `Utenti` (`ID_utente`, `username`, `hash_password`, `email`, `tipo`, `stato`, `cod_scuola`) VALUES
(3, 'ciao1', 'a0c299b71a9e59d5ebb07917e70601a3570aa103e99a7bb65a58e780ec9077b1902d1dedb31b1457beda595fe4d71d779b6ca9cad476266cc07590e31d84b206', 'ciao@ciao.it', 'ADMIN', 'BLOCCATO', NULL),
(4, 'gbdzddg', 'a0c299b71a9e59d5ebb07917e70601a3570aa103e99a7bb65a58e780ec9077b1902d1dedb31b1457beda595fe4d71d779b6ca9cad476266cc07590e31d84b206', 'utente@utente.it', 'SCOLASTICO', 'BLOCCATO', 'ANIS023002'),
(9, 'admin', '$2y$12$V1YMHphV7Y5fdfq3jFnpseLHlZYE4EhLcym3Z4oEZuyOFPLtQ.pG2', 'admin@admin.it', 'ADMIN', 'ATTIVO', NULL),
(10, 'Professore', '$2y$12$vlorRkXKupf.P6XnYXc2hOsAj5OXnFuGcJ.ajCfc.oeGy4KAl/Iba', 'scolastico@scolastico.it', 'SCOLASTICO', 'ATTIVO', NULL);

--
-- Trigger `Utenti`
--
DELIMITER $$
CREATE TRIGGER `default_admin` BEFORE INSERT ON `Utenti` FOR EACH ROW BEGIN
    IF NEW.tipo = "ADMIN" THEN
        SET NEW.cod_scuola = NULL;
		END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `Zone`
--

CREATE TABLE `Zone` (
  `ID_zona` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `Zone`
--

INSERT INTO `Zone` (`ID_zona`, `nome`) VALUES
(1, 'Zona 2'),
(2, 'Cingoli1'),
(8, 'Jesi'),
(13, 'Filottrano nord'),
(14, 'Acqualagna');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `Ambiti`
--
ALTER TABLE `Ambiti`
  ADD PRIMARY KEY (`ID_ambito`);

--
-- Indici per le tabelle `Citta`
--
ALTER TABLE `Citta`
  ADD PRIMARY KEY (`ID_citta`),
  ADD KEY `idx_citta_sigla_provincia_nome` (`sigla_provincia`,`nome`),
  ADD KEY `idx_citta_id_zona` (`id_zona`);

--
-- Indici per le tabelle `Eventi`
--
ALTER TABLE `Eventi`
  ADD PRIMARY KEY (`ID_evento`),
  ADD KEY `idx_eventi_scuola_attivi_data` (`cod_scuola`,`data_eliminazione`,`ora_inizio`),
  ADD KEY `idx_eventi_citta_attivi_data` (`id_citta`,`data_eliminazione`,`ora_inizio`),
  ADD KEY `idx_eventi_id_foto` (`id_foto`);

--
-- Indici per le tabelle `Foto`
--
ALTER TABLE `Foto`
  ADD PRIMARY KEY (`ID_foto`);

--
-- Indici per le tabelle `Indirizzi_studio`
--
ALTER TABLE `Indirizzi_studio`
  ADD PRIMARY KEY (`ID_indirizzo`),
  ADD UNIQUE KEY `ordine` (`ordine`);

--
-- Indici per le tabelle `Links`
--
ALTER TABLE `Links`
  ADD PRIMARY KEY (`ID_link`),
  ADD UNIQUE KEY `n_ordine` (`n_ordine`),
  ADD KEY `idx_links_attivi_ordine` (`data_eliminazione`,`n_ordine`),
  ADD KEY `idx_links_id_foto` (`id_foto`);

--
-- Indici per le tabelle `Progetti`
--
ALTER TABLE `Progetti`
  ADD PRIMARY KEY (`ID_progetto`),
  ADD KEY `idx_progetti_attivi_ordine` (`data_eliminazione`,`n_ordine`),
  ADD KEY `idx_progetti_id_foto` (`id_foto`),
  ADD KEY `n_ordine` (`n_ordine`) USING BTREE;

--
-- Indici per le tabelle `Province`
--
ALTER TABLE `Province`
  ADD PRIMARY KEY (`sigla`);

--
-- Indici per le tabelle `Scuole`
--
ALTER TABLE `Scuole`
  ADD PRIMARY KEY (`COD_meccanografico`),
  ADD KEY `idx_scuole_id_citta_nome` (`id_citta`,`nome`),
  ADD KEY `idx_scuole_id_foto` (`id_foto`);

--
-- Indici per le tabelle `Scuole_Ambiti`
--
ALTER TABLE `Scuole_Ambiti`
  ADD PRIMARY KEY (`cod_scuola`,`id_ambito`),
  ADD KEY `idx_scuole_ambiti_id_ambito_scuola` (`id_ambito`,`cod_scuola`);

--
-- Indici per le tabelle `Scuole_Indirizzi`
--
ALTER TABLE `Scuole_Indirizzi`
  ADD PRIMARY KEY (`cod_scuola`,`id_indirizzo`),
  ADD KEY `idx_scuole_indirizzi_scuola_ordine` (`cod_scuola`,`n_ordine`),
  ADD KEY `idx_scuole_indirizzi_indirizzo_scuola` (`id_indirizzo`,`cod_scuola`);

--
-- Indici per le tabelle `Utenti`
--
ALTER TABLE `Utenti`
  ADD PRIMARY KEY (`ID_utente`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_utenti_scuola_tipo_stato` (`cod_scuola`,`tipo`,`stato`);

--
-- Indici per le tabelle `Zone`
--
ALTER TABLE `Zone`
  ADD PRIMARY KEY (`ID_zona`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `Ambiti`
--
ALTER TABLE `Ambiti`
  MODIFY `ID_ambito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `Citta`
--
ALTER TABLE `Citta`
  MODIFY `ID_citta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1794;

--
-- AUTO_INCREMENT per la tabella `Eventi`
--
ALTER TABLE `Eventi`
  MODIFY `ID_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT per la tabella `Foto`
--
ALTER TABLE `Foto`
  MODIFY `ID_foto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT per la tabella `Indirizzi_studio`
--
ALTER TABLE `Indirizzi_studio`
  MODIFY `ID_indirizzo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT per la tabella `Links`
--
ALTER TABLE `Links`
  MODIFY `ID_link` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT per la tabella `Progetti`
--
ALTER TABLE `Progetti`
  MODIFY `ID_progetto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT per la tabella `Utenti`
--
ALTER TABLE `Utenti`
  MODIFY `ID_utente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `Zone`
--
ALTER TABLE `Zone`
  MODIFY `ID_zona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

-- --------------------------------------------------------

--
-- Struttura per vista `GetAmbiti`
--
DROP TABLE IF EXISTS `GetAmbiti`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetAmbiti`  AS SELECT `Ambiti`.`ID_ambito` AS `ID_ambito`, `Ambiti`.`nome` AS `nome`, `Ambiti`.`descrizione` AS `descrizione` FROM `Ambiti` ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetCitta`
--
DROP TABLE IF EXISTS `GetCitta`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetCitta`  AS SELECT `Citta`.`ID_citta` AS `ID_citta`, `Citta`.`nome` AS `nome`, `Citta`.`sigla_provincia` AS `sigla_provincia`, `Citta`.`cap` AS `cap`, `Citta`.`id_zona` AS `id_zona` FROM `Citta` ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetEventiAttivi`
--
DROP TABLE IF EXISTS `GetEventiAttivi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetEventiAttivi`  AS SELECT `E`.`ID_evento` AS `ID_evento`, `E`.`titolo` AS `titolo`, `E`.`descrizione` AS `descrizione`, `E`.`target` AS `target`, `E`.`ora_inizio` AS `ora_inizio`, `E`.`ora_fine` AS `ora_fine`, `E`.`visibile` AS `visibile`, `E`.`prenotabile` AS `prenotabile`, `E`.`via_P` AS `via_P`, `E`.`n_civico_P` AS `n_civico_P`, `E`.`coordinate` AS `coordinate`, `E`.`descrizione_breve` AS `descrizione_breve`, `E`.`id_citta` AS `id_citta`, `C`.`nome` AS `nome_citta`, `C`.`sigla_provincia` AS `sigla_provincia`, `E`.`cod_scuola` AS `cod_scuola`, `S`.`nome` AS `nome_scuola`, `E`.`id_foto` AS `id_foto`, `F`.`path_foto` AS `path_foto` FROM (((`Eventi` `E` left join `Citta` `C` on(`E`.`id_citta` = `C`.`ID_citta`)) left join `Scuole` `S` on(`E`.`cod_scuola` = `S`.`COD_meccanografico`)) left join `Foto` `F` on(`E`.`id_foto` = `F`.`ID_foto`)) WHERE `E`.`data_eliminazione` is null ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetFoto`
--
DROP TABLE IF EXISTS `GetFoto`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetFoto`  AS SELECT `Foto`.`ID_foto` AS `ID_foto`, `Foto`.`path_foto` AS `path_foto` FROM `Foto` WHERE `Foto`.`data_eliminazione` is null ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetIndirizziStudio`
--
DROP TABLE IF EXISTS `GetIndirizziStudio`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetIndirizziStudio`  AS SELECT `Indirizzi_studio`.`ID_indirizzo` AS `ID_indirizzo`, `Indirizzi_studio`.`nome` AS `nome`, `Indirizzi_studio`.`ordine` AS `ordine` FROM `Indirizzi_studio` ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetLinks`
--
DROP TABLE IF EXISTS `GetLinks`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetLinks`  AS SELECT `Links`.`ID_link` AS `ID_link`, `Links`.`titolo` AS `titolo`, `Links`.`url_link` AS `url_link`, `Links`.`descrizione` AS `descrizione`, `Links`.`n_ordine` AS `n_ordine`, `Links`.`id_foto` AS `id_foto` FROM `Links` WHERE `Links`.`data_eliminazione` is null ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetProgetti`
--
DROP TABLE IF EXISTS `GetProgetti`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetProgetti`  AS SELECT `Progetti`.`ID_progetto` AS `ID_progetto`, `Progetti`.`titolo` AS `titolo`, `Progetti`.`descrizione` AS `descrizione`, `Progetti`.`n_ordine` AS `n_ordine`, `Progetti`.`id_foto` AS `id_foto` FROM `Progetti` WHERE `Progetti`.`data_eliminazione` is null ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetProvince`
--
DROP TABLE IF EXISTS `GetProvince`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetProvince`  AS SELECT `Province`.`sigla` AS `sigla`, `Province`.`nome` AS `nome` FROM `Province` ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetScuole`
--
DROP TABLE IF EXISTS `GetScuole`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetScuole`  AS SELECT `Scuole`.`COD_meccanografico` AS `COD_meccanografico`, `Scuole`.`nome` AS `nome`, `Scuole`.`descrizione` AS `descrizione`, `Scuole`.`sito` AS `sito`, `Scuole`.`via` AS `via`, `Scuole`.`n_civico` AS `n_civico`, `Scuole`.`id_citta` AS `id_citta`, `Scuole`.`coordinate` AS `coordinate`, `Scuole`.`id_foto` AS `id_foto` FROM `Scuole` ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetScuoleAmbiti`
--
DROP TABLE IF EXISTS `GetScuoleAmbiti`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetScuoleAmbiti`  AS SELECT `Scuole_Ambiti`.`cod_scuola` AS `cod_scuola`, `Scuole_Ambiti`.`id_ambito` AS `id_ambito` FROM `Scuole_Ambiti` ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetScuoleIndirizzi`
--
DROP TABLE IF EXISTS `GetScuoleIndirizzi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetScuoleIndirizzi`  AS SELECT `Scuole_Indirizzi`.`cod_scuola` AS `cod_scuola`, `Scuole_Indirizzi`.`id_indirizzo` AS `id_indirizzo`, `Scuole_Indirizzi`.`n_ordine` AS `n_ordine` FROM `Scuole_Indirizzi` ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetUtenti`
--
DROP TABLE IF EXISTS `GetUtenti`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetUtenti`  AS SELECT `Utenti`.`ID_utente` AS `ID_utente`, `Utenti`.`username` AS `username`, `Utenti`.`email` AS `email`, `Utenti`.`tipo` AS `tipo`, `Utenti`.`stato` AS `stato`, `Utenti`.`cod_scuola` AS `cod_scuola` FROM `Utenti` ;

-- --------------------------------------------------------

--
-- Struttura per vista `GetZone`
--
DROP TABLE IF EXISTS `GetZone`;

CREATE ALGORITHM=UNDEFINED DEFINER=`uawit4pc_3elleorienta`@`localhost` SQL SECURITY DEFINER VIEW `GetZone`  AS SELECT `Zone`.`ID_zona` AS `ID_zona`, `Zone`.`nome` AS `nome` FROM `Zone` ;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `Citta`
--
ALTER TABLE `Citta`
  ADD CONSTRAINT `fk_id_zona` FOREIGN KEY (`id_zona`) REFERENCES `Zone` (`ID_zona`),
  ADD CONSTRAINT `fk_sigla_provincia` FOREIGN KEY (`sigla_provincia`) REFERENCES `Province` (`sigla`);

--
-- Limiti per la tabella `Eventi`
--
ALTER TABLE `Eventi`
  ADD CONSTRAINT `fk_CittaEv` FOREIGN KEY (`id_citta`) REFERENCES `Citta` (`ID_citta`),
  ADD CONSTRAINT `fk_FotoEv` FOREIGN KEY (`id_foto`) REFERENCES `Foto` (`ID_foto`),
  ADD CONSTRAINT `fk_ScuolaEv` FOREIGN KEY (`cod_scuola`) REFERENCES `Scuole` (`COD_meccanografico`);

--
-- Limiti per la tabella `Links`
--
ALTER TABLE `Links`
  ADD CONSTRAINT `fk_fotoLinks` FOREIGN KEY (`id_foto`) REFERENCES `Foto` (`ID_foto`);

--
-- Limiti per la tabella `Progetti`
--
ALTER TABLE `Progetti`
  ADD CONSTRAINT `fk_fotoProgetti` FOREIGN KEY (`id_foto`) REFERENCES `Foto` (`ID_foto`);

--
-- Limiti per la tabella `Scuole`
--
ALTER TABLE `Scuole`
  ADD CONSTRAINT `fk_FotoScuola` FOREIGN KEY (`id_foto`) REFERENCES `Foto` (`ID_foto`);

--
-- Limiti per la tabella `Scuole_Ambiti`
--
ALTER TABLE `Scuole_Ambiti`
  ADD CONSTRAINT `fk_AmbitoSc` FOREIGN KEY (`id_ambito`) REFERENCES `Ambiti` (`ID_ambito`),
  ADD CONSTRAINT `fk_ScuolaAmb` FOREIGN KEY (`cod_scuola`) REFERENCES `Scuole` (`COD_meccanografico`);

--
-- Limiti per la tabella `Scuole_Indirizzi`
--
ALTER TABLE `Scuole_Indirizzi`
  ADD CONSTRAINT `fk_IndirizzoSc` FOREIGN KEY (`id_indirizzo`) REFERENCES `Indirizzi_studio` (`ID_indirizzo`),
  ADD CONSTRAINT `fk_ScuolaInd` FOREIGN KEY (`cod_scuola`) REFERENCES `Scuole` (`COD_meccanografico`);

--
-- Limiti per la tabella `Utenti`
--
ALTER TABLE `Utenti`
  ADD CONSTRAINT `fk_scuola` FOREIGN KEY (`cod_scuola`) REFERENCES `Scuole` (`COD_meccanografico`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
