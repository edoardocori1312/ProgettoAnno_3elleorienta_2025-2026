# Svelati — 3elleorienta

Piattaforma di orientamento scolastico per reti territoriali nelle Marche.
Progetto scolastico multidisciplinare (5AM + 5DM, ITIS Marconi-Pieralisi Jesi).

## Stack tecnico

- **PHP** (server-rendered, senza framework)
- **MySQL / MariaDB** con MySQLi e prepared statements
- **Bootstrap 5.3.3** (CDN)
- **Leaflet 1.9.4** (mappa eventi, CDN)

## Struttura

```
config/db.php                      # Connessione DB (unica per tutto il progetto)
lib/auth.php                       # Sessione, login, ruoli, flash
lib/layout.php                     # Render helpers per head/nav/sidebar/footer
lib/foto.php                       # Upload e associazione foto
lib/geo.php                        # Geocodifica via Nominatim
db/schema.sql                      # Schema MySQL pulito
db/seed.sql                        # Dati di esempio + utenti di test
public/                            # Sito pubblico (index, ambiti, orientati, eventi)
admin/                             # Pannello amministrativo
admin/gestione/gestione_*.php      # Funzioni DB per ogni dominio
uploads/                           # Foto caricate dagli utenti
```

## Setup

### 1. Database

```bash
mysql -u root -proot < db/schema.sql
mysql -u root -proot treelleorienta < db/seed.sql
```

Verifica importazione:
```sql
USE treelleorienta;
SELECT COUNT(*) FROM Scuole;
SELECT username, tipo, cod_scuola FROM Utenti;
```

### 2. Configurazione DB

Modifica `config/db.php` se le credenziali MySQL sono diverse da `root/root`:
```php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'treelleorienta';
```

### 3. Server di sviluppo (PHP built-in)

```bash
# Sito pubblico: http://localhost:8000
php -S localhost:8000 -t public

# Pannello admin: http://localhost:8001
php -S localhost:8001 -t admin
```

Oppure, con XAMPP/UniserverZ in `htdocs/`:
- `http://localhost/ProgettoAnno_3elleorienta_2025-2026/public/`
- `http://localhost/ProgettoAnno_3elleorienta_2025-2026/admin/`

### 4. Logo

Aggiungi il logo del progetto in:
- `admin/assets/img/logo.png`
- `public/assets/img/logo.png`

## Avvio con Docker

Ambiente completo (MariaDB + phpMyAdmin + app) con un solo comando. Il codice è bind-mountato: le modifiche su host sono subito visibili nel container.

### Requisiti

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)

### Primo avvio

```bash
docker compose up -d --build
```

Attendi ~20 s che il DB diventi healthy, poi apri:

| Servizio       | URL                              |
|----------------|----------------------------------|
| Sito pubblico  | http://localhost:8080/public/    |
| Pannello admin | http://localhost:8080/admin/     |
| phpMyAdmin     | http://localhost:8081            |

Credenziali phpMyAdmin: `root` / `root`

Le immagini demo vengono generate automaticamente in `uploads/` al primo avvio.

### Ri-seedare il database

```bash
docker compose down -v
docker compose up -d
```

> **Nota:** il volume `dbdata` viene ricreato da zero — tutti i dati inseriti vengono persi.

## Credenziali di test

| Ruolo      | Email              | Password    |
|------------|--------------------|-------------|
| ADMIN      | admin@svelati.it   | password123 |
| SCOLASTICO | scuola@svelati.it  | password123 |

L'utente SCOLASTICO è associato alla scuola `ANIS01100A`.

## Ruoli

| Funzione                    | ADMIN | SCOLASTICO |
|-----------------------------|-------|------------|
| Gestione scuole (tutte)     | ✅    | ✖          |
| Modifica propria scuola     | ✅    | ✅         |
| Gestione zone               | ✅    | ✖          |
| Gestione eventi (tutti)     | ✅    | ✖          |
| Gestione propri eventi      | ✅    | ✅         |
| Gestione progetti           | ✅    | ✖          |
| Gestione link utili         | ✅    | ✖          |
| Gestione utenti             | ✅    | ✖          |
| Cambio propria password     | ✅    | ✅         |
