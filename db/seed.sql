-- ============================================================
-- Seed data for treelleorienta
-- Test credentials:
--   ADMIN      -> admin@svelati.it  / password123
--   SCOLASTICO -> scuola@svelati.it / password123
-- ============================================================

USE treelleorienta;

-- ---- Foto ----
INSERT INTO Foto (ID_foto, path_foto) VALUES
    (1,  'uploads/seed_scuola1.jpg'),
    (2,  'uploads/seed_scuola2.jpg'),
    (3,  'uploads/seed_scuola3.jpg'),
    (4,  'uploads/seed_scuola4.jpg'),
    (5,  'uploads/seed_scuola5.jpg'),
    (6,  'uploads/seed_evento1.jpg'),
    (7,  'uploads/seed_evento2.jpg'),
    (8,  'uploads/seed_evento3.jpg'),
    (9,  'uploads/seed_evento4.jpg'),
    (10, 'uploads/seed_evento5.jpg'),
    (11, 'uploads/progetto_reti.jpg'),
    (12, 'uploads/progetto_3l.jpg'),
    (13, 'uploads/seed_progetto3.jpg'),
    (14, 'uploads/link_sorprendo.png'),
    (15, 'uploads/link_scuolainchiaro.png'),
    (16, 'uploads/link_iscrizioni.jpg'),
    (17, 'uploads/link_sceltasuperiori.png'),
    (18, 'uploads/link_secondariadue.jpg'),
    (19, 'uploads/scuola_placeholder.jpg'),
    (20, 'uploads/evento_placeholder.png');

-- ---- Province ----
INSERT INTO Province (sigla, nome) VALUES
    ('AN', 'Ancona'),
    ('MC', 'Macerata'),
    ('PU', 'Pesaro e Urbino');

-- ---- Zone ----
INSERT INTO Zone (ID_zona, nome) VALUES
    (1, 'Jesi e Vallesina'),
    (2, 'Ancona'),
    (3, 'Macerata');

-- ---- Citta ----
INSERT INTO Citta (ID_citta, nome, sigla_provincia, id_zona) VALUES
    (1, 'Jesi',      'AN', 1),
    (2, 'Ancona',    'AN', 2),
    (3, 'Senigallia','AN', 2),
    (4, 'Macerata',  'MC', 3);

-- ---- Ambiti ----
INSERT INTO Ambiti (ID_ambito, nome, descrizione) VALUES
    (1, 'Scuola e Formazione',  'Percorsi scolastici e formativi sul territorio.'),
    (2, 'Lavoro e Professioni', 'Opportunita di lavoro e sbocchi professionali.'),
    (3, 'Territorio e Reti',    'Associazioni e reti territoriali.'),
    (4, 'Innovazione',          'Tecnologia, ricerca e innovazione.');

-- ---- Indirizzi di studio ----
INSERT INTO Indirizzi_studio (ID_indirizzo, nome, ordine) VALUES
    (1, 'Liceo Scientifico',      1),
    (2, 'Liceo Classico',         2),
    (3, 'Istituto Tecnico',       3),
    (4, 'Istituto Professionale', 4);

-- ---- Scuole (5: 3 originali + 2 nuove) ----
INSERT INTO Scuole (COD_meccanografico, nome, descrizione, via, n_civico, id_citta, sito, latitudine, longitudine, id_foto) VALUES
    ('ANIS01100A', 'IIS Galilei',
     'Istituto di istruzione superiore con sede a Jesi, offre percorsi tecnici e scientifici.',
     'Via Galilei', 10, 1, 'http://www.galilei-jesi.gov.it', 43.522800, 13.242100, 19),
    ('ANPS02200B', 'Liceo Leonardo da Vinci',
     'Liceo scientifico e classico ad Ancona con ampia offerta formativa.',
     'Via Leonardo da Vinci', 20, 2, 'http://www.liceodavinci.gov.it', 43.613600, 13.518800, 19),
    ('ANIT03300C', 'ITT Marconi',
     'Istituto tecnico tecnologico a Senigallia, specializzato in informatica e elettronica.',
     'Via Marconi', 5, 3, 'http://www.marconi-senigallia.gov.it', 43.715700, 13.217500, 19),
    ('ANIS04400D', 'IIS Vanvitelli',
     'Istituto superiore di Ancona con indirizzi classico e tecnico industriale.',
     'Via Vanvitelli', 15, 2, 'http://www.vanvitelli-ancona.gov.it', 43.617000, 13.519000, 19),
    ('ANSL05500E', 'Liceo E. Medi',
     'Liceo scientifico e classico a Senigallia con forte tradizione umanistica.',
     'Via Carducci', 8, 3, 'http://www.medioliceo.gov.it', 43.714500, 13.219000, 19);

-- ---- Scuole_Ambiti ----
INSERT INTO Scuole_Ambiti (cod_scuola, id_ambito) VALUES
    ('ANIS01100A', 1), ('ANIS01100A', 2),
    ('ANPS02200B', 2), ('ANPS02200B', 3),
    ('ANIT03300C', 3), ('ANIT03300C', 4),
    ('ANIS04400D', 2), ('ANIS04400D', 4),
    ('ANSL05500E', 1), ('ANSL05500E', 3);

-- ---- Scuole_Indirizzi ----
INSERT INTO Scuole_Indirizzi (cod_scuola, id_indirizzo, n_ordine) VALUES
    ('ANIS01100A', 1, 1), ('ANIS01100A', 3, 2),
    ('ANPS02200B', 1, 1), ('ANPS02200B', 2, 2),
    ('ANIT03300C', 3, 1), ('ANIT03300C', 4, 2),
    ('ANIS04400D', 2, 1), ('ANIS04400D', 3, 2),
    ('ANSL05500E', 1, 1), ('ANSL05500E', 2, 2);

-- ---- Eventi (6: 4 originali + 2 nuovi) ----
INSERT INTO Eventi (titolo, descrizione, descrizione_breve, target, ora_inizio, ora_fine, visibile, prenotabile, via_P, n_civico_P, latitudine, longitudine, id_citta, cod_scuola, id_foto) VALUES
    ('Open Day IIS Galilei',
     'Giornata di orientamento per studenti delle medie. Visita ai laboratori e incontro con i docenti.',
     'Open day con laboratori aperti.',
     'SCOLASTICO',
     '2026-01-17 09:00:00', '2026-01-17 13:00:00', 1, 1,
     NULL, NULL, NULL, NULL, NULL, 'ANIS01100A', 20),
    ('Orientamento ITT Marconi',
     'Presentazione dei corsi tecnici e incontro con studenti e famiglie.',
     'Presentazione corsi tecnici.',
     'SCOLASTICO',
     '2026-02-14 10:00:00', '2026-02-14 12:00:00', 1, 0,
     NULL, NULL, NULL, NULL, NULL, 'ANIT03300C', 20),
    ('Fiera dell Orientamento Jesi',
     'Evento territoriale con stand di tutte le scuole della zona Jesi e Vallesina.',
     'Fiera con stand di tutte le scuole.',
     'TERRITORIALE',
     '2026-03-07 09:00:00', '2026-03-07 17:00:00', 1, 0,
     'Via della Repubblica', 1, 43.523100, 13.242500, 1, NULL, 20),
    ('Evento Eliminato',
     'Questo evento e stato eliminato (soft delete demo).',
     'Demo soft delete.',
     'SCOLASTICO',
     '2025-12-01 09:00:00', '2025-12-01 11:00:00', 0, 0,
     NULL, NULL, NULL, NULL, NULL, 'ANPS02200B', 20),
    ('Workshop Coding Ancona',
     'Workshop pratico di programmazione per studenti degli istituti tecnici di Ancona. Partecipazione gratuita con iscrizione online.',
     'Workshop di programmazione per istituti tecnici.',
     'SCOLASTICO',
     '2026-04-20 14:00:00', '2026-04-20 17:00:00', 1, 1,
     NULL, NULL, NULL, NULL, NULL, 'ANIS04400D', 20),
    ('Giornata Orientamento Macerata',
     'Grande evento territoriale con la partecipazione di tutte le scuole superiori della provincia di Macerata.',
     'Orientamento territoriale Macerata.',
     'TERRITORIALE',
     '2026-05-10 09:00:00', '2026-05-10 16:00:00', 1, 0,
     'Via Gramsci', 5, 43.300800, 13.453600, 4, NULL, 20);

UPDATE Eventi SET data_eliminazione = NOW() WHERE titolo = 'Evento Eliminato';

-- ---- Progetti ----
INSERT INTO Progetti (titolo, descrizione, n_ordine, id_foto) VALUES
    ('Reti Territoriali',
'Il progetto Reti Territoriali per l’Orientamento della Regione Marche ha l’obiettivo primario di migliorare l’orientamento scolastico e professionale degli studenti, creando sinergie tra le diverse scuole del territorio. Quest’iniziativa mira a supportare gli studenti nella transizione tra i vari gradi di istruzione, promuovendo percorsi formativi che rispettino le inclinazioni, gli interessi e le potenzialità degli alunni.

Obiettivi principali del progetto:
Creazione di una rete di scuole per il miglioramento dell’orientamento.
Organizzazione di eventi di orientamento e incontri tra studenti ed ex studenti per condividere esperienze.
Sviluppo di attività formative e informative attraverso il coinvolgimento di esperti.
Promozione della cooperazione tra scuole, famiglie e istituzioni.

Scuole partecipanti alla rete:
I.C. Bartolini – Cupramontana
I.C. Carlo Urbani – JESI
I.C. Carlo Urbani – Moie di Maiolati
I.C. Federico II – JESI
I.C. Lorenzo Lotto – JESI
I.C. Rita Levi Montalcini – Chiaravalle
I.C. San Francesco – JESI
IIS Cuppari Salvati – Jesi
IIS Galileo Galilei – Jesi (Capofila)
IIS Marconi Pieralisi – JESI
Liceo Classico V. Emanuele II – JESI
Liceo Scientifico L. Da Vinci – JESI

Il Capofila del progetto, l’IIS Galileo Galilei di Jesi, coordina le attività formative e le iniziative di orientamento per garantire un processo educativo efficiente e in grado di rispondere alle esigenze del territorio.

Progetto cod. SIFORM 1095939 denominazione: Svelati – codice bando siform2 ORIENTAMENTO CONTINUO 2024 – DGR 1591 del 06/11/2023 – PR FSE+ 2021/2027 Asse 2 Istruzione e Formazione OS 4.e (4) Orientamento continuo Campo di intervento 149 Avviso pubblico relativo alla presentazione di progetti di Reti territoriali per l’orientamento approvato con DDS n. 336 del 15/12/2023. CUP: C41I24000070002',
     1, 11),
    ('3L LifeLongLearning',
'3L LifeLongLearning.it è un progetto di orientamento a cui partecipano 11 scuole di primo grado e 9 di secondo grado dei territori di Jesi e di Fabriano.

Il progetto, presentato dall’IIS Cuppari Salvati, è finanziato dalla Regione Marche ed ha lo scopo di mettere a sistema l’offerta di orientamento con azioni di carattere informativo, formativo e consulenziale rivolte agli studenti di scuola secondaria di primo grado e dei primi due anni dei percorsi del secondo ciclo di istruzione.

L’idea progettuale coinvolge docenti orientatori, studenti e famiglie di un vasto territorio e rafforza le azioni di orientamento messe in atto da ogni singola scuola partner grazie ad una più ampia innovazione digitale e ad alternative Unità Didattiche di Apprendimento (UDA) la cui finalità è la valorizzazione del processo formativo rispetto a quello informativo e la progettazione condivisa favorirà, a livello individuale, la scoperta di interessi ed attitudini.

3L LifeLongLearning.it propone di:
potenziare Unità Didattiche di Apprendimento (UDA) corrispondenti a specifici ambiti di interesse;
produrre e condividere con studenti e famiglie azioni informative e formative;
avviare percorsi di ricerca-azione sperimentali di didattica orientativa;
attivare percorsi di consulenza orientativa per studenti e famiglie;
costruire la piattaforma 3elleorienta.it per favorire, in modalità sincrona ed asincrona, l’orientamento formativo d’informativo e condividere azioni e materiali pertinenti;
realizzare e mettere a disposizione degli studenti un APP su Smartphone per avere informazioni sulle attività orientative;
far conoscere la piattaforma Sorprendo per aiutare gli studenti a prendere decisioni e a realizzare il proprio personale percorso di orientamento;
monitorare e valutare l’efficacia ed efficienza degli obbiettivi raggiunti.

Considerato poi che l’azione orientativa non deve essere isolata, circoscritta solo ed esclusivamente al momento di passaggio tra un ordine di scuola e l’altro ma deve accompagnare costantemente la vita di ogni individuo il progetto 3L LifeLongLearning.it intende consentire allo studente e successivamente anche all’adulto di:
valutare consapevolmente un percorso di istruzione e formazione per il quale il singolo studente riconosce di avere attitudine e interesse tra la gamma di tutti quelli proposti nel territorio;
valutare in modo autonomo e critico il livello qualitativo di una proposta formativa finalizzata all’acquisizione di competenze scegliendo quelle maggiormente efficaci;
valutare in modo responsabile il contesto territoriale, professionale e culturale di riferimento cercando di comprendere il proprio ruolo.

ASSE III P.Inv 10.4 D.D.P.F. n. 1050/IFD del 26/06/2019. Finanziato dalla Regione Marche con D.D.P.F. n. 71/IFD del 30/01/2020 – Cod. SIFORM 1015864',
     2, 12);

-- ---- Links ----
INSERT INTO Links (titolo, descrizione, indirizzo, n_ordine, id_foto) VALUES
    ('sorprendo',                              'descrizione temporanea', 'https://www.sorprendo.it/',                                                            1, 14),
    ('scuola in chiaro',                       'descrizione temporanea', 'https://unica.istruzione.gov.it/sic',                                                  2, 15),
    ('Iscrizioni Online',                      'descrizione temporanea', 'https://www.istruzione.it/iscrizionionline/',                                           3, 16),
    ('Scegliere il percorso di scuola superiore', 'descrizione temporanea', 'https://www.mim.gov.it/web/guest/scegliere-il-percorso-di-scuola-superiore',        4, 17),
    ('Scuola secondaria di secondo grado',     'descrizione temporanea', 'https://www.mim.gov.it/web/guest/scuola-secondaria-di-secondo-grado',                  5, 18);

-- ---- Utenti (password: password123) ----
INSERT INTO Utenti (username, hash_password, email, tipo, stato, cod_scuola) VALUES
    ('admin',  '$2y$12$xMQsP2xSzbuKzv86jQJ2jOBNIPiWMrMB46WIDdc4BjQPiqOnxYQS6', 'admin@svelati.it',  'ADMIN',      'ATTIVO', NULL),
    ('scuola', '$2y$12$8XTODNeykKj2bhrFiDmjEunTM19plThLnd37Q7NKP.amBeGBVY3qW', 'scuola@svelati.it', 'SCOLASTICO', 'ATTIVO', 'ANIS01100A');
