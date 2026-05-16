-- ============================================================
-- Seed data for treelleorienta
-- Test credentials:
--   ADMIN    -> admin@svelati.it  / password123
--   SCOLASTICO -> scuola@svelati.it / password123
-- ============================================================

USE treelleorienta;

-- Foto placeholder (used by seed schools/events)
INSERT INTO Foto (ID_foto, path_foto) VALUES
    (1, 'uploads/placeholder.jpg'),
    (2, 'uploads/placeholder.jpg'),
    (3, 'uploads/placeholder.jpg');

-- Province
INSERT INTO Province (sigla, nome) VALUES
    ('AN', 'Ancona'),
    ('MC', 'Macerata'),
    ('PU', 'Pesaro e Urbino');

-- Zone
INSERT INTO Zone (ID_zona, nome) VALUES
    (1, 'Jesi e Vallesina'),
    (2, 'Ancona'),
    (3, 'Macerata');

-- Citta
INSERT INTO Citta (ID_citta, nome, sigla_provincia, id_zona) VALUES
    (1, 'Jesi',      'AN', 1),
    (2, 'Ancona',    'AN', 2),
    (3, 'Senigallia','AN', 2),
    (4, 'Macerata',  'MC', 3);

-- Ambiti
INSERT INTO Ambiti (ID_ambito, nome, descrizione) VALUES
    (1, 'Scuola e Formazione', 'Percorsi scolastici e formativi sul territorio.'),
    (2, 'Lavoro e Professioni', 'Opportunita di lavoro e sbocchi professionali.'),
    (3, 'Territorio e Reti',   'Associazioni e reti territoriali.'),
    (4, 'Innovazione',         'Tecnologia, ricerca e innovazione.');

-- Indirizzi di studio
INSERT INTO Indirizzi_studio (ID_indirizzo, nome, ordine) VALUES
    (1, 'Liceo Scientifico',    1),
    (2, 'Liceo Classico',       2),
    (3, 'Istituto Tecnico',     3),
    (4, 'Istituto Professionale', 4);

-- Scuole
INSERT INTO Scuole (COD_meccanografico, nome, descrizione, via, n_civico, id_citta, sito, latitudine, longitudine, id_foto) VALUES
    ('ANIS01100A', 'IIS Galilei',
     'Istituto di istruzione superiore con sede a Jesi, offre percorsi tecnici e scientifici.',
     'Via Galilei', 10, 1, 'http://www.galilei-jesi.gov.it', 43.522800, 13.242100, 1),
    ('ANPS02200B', 'Liceo Leonardo da Vinci',
     'Liceo scientifico e classico ad Ancona con ampia offerta formativa.',
     'Via Leonardo da Vinci', 20, 2, 'http://www.liceodavinci.gov.it', 43.613600, 13.518800, 2),
    ('ANIT03300C', 'ITT Marconi',
     'Istituto tecnico tecnologico a Senigallia, specializzato in informatica e elettronica.',
     'Via Marconi', 5, 3, 'http://www.marconi-senigallia.gov.it', 43.715700, 13.217500, 3);

-- Scuole_Ambiti
INSERT INTO Scuole_Ambiti (cod_scuola, id_ambito) VALUES
    ('ANIS01100A', 1), ('ANIS01100A', 2),
    ('ANPS02200B', 2), ('ANPS02200B', 3),
    ('ANIT03300C', 3), ('ANIT03300C', 4);

-- Scuole_Indirizzi
INSERT INTO Scuole_Indirizzi (cod_scuola, id_indirizzo, n_ordine) VALUES
    ('ANIS01100A', 1, 1), ('ANIS01100A', 3, 2),
    ('ANPS02200B', 1, 1), ('ANPS02200B', 2, 2),
    ('ANIT03300C', 3, 1), ('ANIT03300C', 4, 2);

-- Eventi
-- target and nulled fields set explicitly (no trigger)
INSERT INTO Eventi (titolo, descrizione, descrizione_breve, target, ora_inizio, ora_fine, visibile, prenotabile, via_P, n_civico_P, latitudine, longitudine, id_citta, cod_scuola, id_foto) VALUES
    ('Open Day IIS Galilei',
     'Giornata di orientamento per studenti delle medie. Visita ai laboratori e incontro con i docenti.',
     'Open day con laboratori aperti.',
     'SCOLASTICO',
     '2026-01-17 09:00:00', '2026-01-17 13:00:00',
     1, 1,
     NULL, NULL, NULL, NULL, NULL, 'ANIS01100A', 1),
    ('Orientamento ITT Marconi',
     'Presentazione dei corsi tecnici e incontro con studenti e famiglie.',
     'Presentazione corsi tecnici.',
     'SCOLASTICO',
     '2026-02-14 10:00:00', '2026-02-14 12:00:00',
     1, 0,
     NULL, NULL, NULL, NULL, NULL, 'ANIT03300C', 2),
    ('Fiera dell Orientamento Jesi',
     'Evento territoriale con stand di tutte le scuole della zona Jesi e Vallesina.',
     'Fiera con stand di tutte le scuole.',
     'TERRITORIALE',
     '2026-03-07 09:00:00', '2026-03-07 17:00:00',
     1, 0,
     'Via della Repubblica', 1, 43.523100, 13.242500, 1, NULL, 3),
    ('Evento Eliminato',
     'Questo evento e stato eliminato (soft delete demo).',
     'Demo soft delete.',
     'SCOLASTICO',
     '2025-12-01 09:00:00', '2025-12-01 11:00:00',
     0, 0,
     NULL, NULL, NULL, NULL, NULL, 'ANPS02200B', 1);

-- soft-delete last event
UPDATE Eventi SET data_eliminazione = NOW() WHERE titolo = 'Evento Eliminato';

-- Progetti
INSERT INTO Progetti (titolo, descrizione, n_ordine, id_foto) VALUES
    ('Svelati - Piattaforma Orientamento', 'Progetto principale di orientamento scolastico della Regione Marche.', 1, 1),
    ('Laboratorio Coding',                 'Laboratorio di informatica per studenti delle medie.',                  2, 2),
    ('Progetto Eliminato',                 'Questo progetto e stato eliminato (soft delete demo).',                 3, NULL);

UPDATE Progetti SET data_eliminazione = CURDATE() WHERE titolo = 'Progetto Eliminato';

-- Links
INSERT INTO Links (titolo, descrizione, indirizzo, n_ordine, id_foto) VALUES
    ('Miur - Orientamento',     'Risorse ufficiali del Ministero per l orientamento scolastico.', 'https://www.miur.gov.it', 1, NULL),
    ('Regione Marche Scuola',   'Portale scolastico della Regione Marche.',                        'https://www.regione.marche.it/Entra-in-Regione/Scuola', 2, NULL),
    ('Link Eliminato',          'Questo link e stato eliminato (soft delete demo).',                'https://example.com', 3, NULL);

UPDATE Links SET data_eliminazione = CURDATE() WHERE titolo = 'Link Eliminato';

-- Utenti (password: password123)
INSERT INTO Utenti (username, hash_password, email, tipo, stato, cod_scuola) VALUES
    ('admin',  '$2y$12$xMQsP2xSzbuKzv86jQJ2jOBNIPiWMrMB46WIDdc4BjQPiqOnxYQS6', 'admin@svelati.it',  'ADMIN',      'ATTIVO', NULL),
    ('scuola', '$2y$12$8XTODNeykKj2bhrFiDmjEunTM19plThLnd37Q7NKP.amBeGBVY3qW', 'scuola@svelati.it', 'SCOLASTICO', 'ATTIVO', 'ANIS01100A');
