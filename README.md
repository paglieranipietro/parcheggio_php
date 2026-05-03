# **README - Sistema di Gestione Parcheggi (Parcheggio PHP)**

## 📋 Descrizione Generale

**Parcheggio PHP** è un'API REST backend per un sistema di gestione parcheggi. Fornisce funzionalità di autenticazione, prenotazione di posti auto, gestione dei parcheggi e profilo utente con supporto per multiple targhe automobilistiche.

Il progetto utilizza il framework **Slim Framework 4** con pattern **MVC** (Model-View-Controller) e autenticazione tramite **JWT**.

---

## 🏗️ Architettura del Progetto

### Struttura delle Cartelle

```
parcheggio_php/
├── index.php                 # Punto d'ingresso dell'applicazione
├── conf/
│   └── config.php           # Configurazione (database, JWT)
├── Controller/              # Logica applicativa
│   ├── AuthController.php   # Autenticazione e gestione profilo
│   ├── ParkingLotController.php  # Gestione parcheggi
│   └── ReservationController.php # Gestione prenotazioni
├── Model/                   # Accesso ai dati (Repository)
│   ├── UtenteRepository.php
│   ├── ParkingLotRepository.php
│   └── ReservationRepository.php
├── Middleware/
│   └── JwtMiddleware.php    # Validazione token JWT
├── Util/
│   └── Connection.php       # Singleton per connessione MySQL
└── db/
    ├── docker-compose.yml   # Configurazione Docker (MySQL + PHPMyAdmin)
    └── 01-init.sql         # Schema database e dati iniziali
```

### Pattern Utilizzati

- **Repository Pattern**: UtenteRepository, ParkingLotRepository, ReservationRepository gestiscono l'accesso al database
- **Singleton Pattern**: Connection è un singleton che mantiene un'unica connessione PDO
- **Dependency Injection**: Slim Framework gestisce le dipendenze tramite Container
- **JWT Authentication**: Middleware che valida i token per le rotte protette

---

## 🔧 Configurazione

### File: `conf/config.php`

```php
const DB_HOST = 'database';      // Host MySQL
const DB_NAME = 'Parcheggio';    // Database
const DB_USER = 'root';          // Utente MySQL
const DB_PASS = 'rootpassword';  // Password MySQL
const JWT_SECRET = 'tuo-super-segreto-parking-2026-cambia-in-prod'; // Chiave JWT
```

**Modifica questi valori** in base al tuo ambiente (sviluppo, test, produzione).

---

## 🔐 Autenticazione

### AuthController

L'autenticazione è basata su **JWT (JSON Web Token)** con scadenza di **24 ore**.

#### Rotte Pubbliche (senza token)

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/register` | Registrazione nuovo utente |
| `POST` | `/api/v1/auth/token` | Login (genera JWT) |
| `GET` | `/api/v1/parking-lots` | Elenco parcheggi disponibili |

#### Rotte Protette (richiedono JWT)

**Format**: `Authorization: Bearer <token_jwt>`

| Metodo | Endpoint | Ruolo | Descrizione |
|--------|----------|-------|-------------|
| `GET` | `/api/v1/auth/me` | User | Profilo utente corrente |
| `PUT` | `/api/v1/auth/me` | User | Aggiorna profilo (nome, cognome, tel, data_nascita) |
| `GET` | `/api/v1/auth/license-plates` | User | Lista targhe registrate |
| `POST` | `/api/v1/auth/license-plates` | User | Aggiungi targa |
| `DELETE` | `/api/v1/auth/license-plates/{id}` | User | Elimina targa |
| `PUT` | `/api/v1/auth/license-plates/{id}/select` | User | Seleziona targa attiva |

### Token JWT

Il token contiene:
```json
{
  "iss": "parking_system",
  "sub": "email@example.com",
  "id_utente": "uuid-v7",
  "role": "utente|admin",
  "nome": "Mario",
  "cognome": "Rossi",
  "iat": 1234567890,
  "exp": 1234654290
}
```

---

## 🅿️ Gestione Parcheggi

### ParkingLotController

#### Rotte Pubbliche

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| `GET` | `/api/v1/parking-lots` | Lista tutti i parcheggi con posti disponibili attuali |
| `GET` | `/api/v1/parking-lots/{id}/availability` | Controlla disponibilità per data/ora specifica |

**Query params per availability**:
- `date` (YYYY-MM-DD) — Data richiesta
- `time` (HH:MM) — Ora inizio (default: 00:00)
- `duration` (float) — Ore di parcheggio

#### Rotte Admin (ruolo: admin)

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| `POST` | `/api/v1/parking-lots` | Crea nuovo parcheggio |
| `PUT` | `/api/v1/parking-lots/{id}` | Aggiorna dati parcheggio |
| `DELETE` | `/api/v1/parking-lots/{id}` | Elimina parcheggio |
| `GET` | `/api/v1/parking-lots/{id}/stats` | Statistiche occupazione |

### Proprieta Parcheggio

```json
{
  "id": 1,
  "name": "Parcheggio Centro",
  "total_spots": 100,
  "address": "Via Roma 1",
  "lat": 45.5415,
  "lng": 10.2160,
  "hourly_rate": 1.50,
  "co2": 100,
  "available_spots": 42
}
```

---

## 🎟️ Prenotazioni

### ReservationController

#### Rotte Protette (User)

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| `POST` | `/api/v1/reservations` | Crea prenotazione |
| `GET` | `/api/v1/reservations/user` | Lista prenotazioni utente |
| `GET` | `/api/v1/reservations/{id}` | Dettagli prenotazione |
| `PUT` | `/api/v1/reservations/{id}` | Aggiorna prenotazione |
| `DELETE` | `/api/v1/reservations/{id}` | Cancella prenotazione (soft delete) |

### Proprieta Prenotazione

```json
{
  "id": "uuid-v4",
  "parking_lot_id": 1,
  "first_name": "Mario",
  "last_name": "Rossi",
  "license_plate": "AB123CD",
  "start_time": "2026-05-03 10:00:00",
  "end_time": "2026-05-03 12:00:00",
  "price": 3.00,
  "status": "ACTIVE",
  "created_at": "2026-05-03 09:30:00",
  "parking_lot_name": "Parcheggio Centro"
}
```

### Stato Prenotazione

- **ACTIVE**: Prenotazione valida e in corso
- **COMPLETED**: Superato l'orario di fine (aggiornamento automatico)
- **CANCELLED**: Cancellata dall'utente (soft delete)

---

## 📊 Logica Disponibilita Posti

### Algoritmo di Calcolo

La disponibilità viene calcolata verificando **le prenotazioni ATTIVE che si sovrappongono** all'intervallo richiesto.

```
Prenotazioni Sovrapposte = quelle dove:
  start_time_esistente < end_time_richiesta AND
  end_time_esistente > start_time_richiesta
  
Posti Liberi = Total Spots - Prenotazioni Sovrapposte
```

**Esempio**:
- Parcheggio: 10 posti totali
- Prenotazione 1: 10:00-12:00
- Prenotazione 2: 11:00-13:00
- Richiesta: 10:30-11:30 → Risultato: 10 - 2 = 8 posti liberi

---

## 🐳 Docker Compose

### File: `db/docker-compose.yml`

Il progetto include una configurazione Docker per avviare facilmente MySQL e PHPMyAdmin:

```bash
cd db
docker-compose up -d
```

Questo avvia:
- **MySQL 8.0**: Servizio database su porta `3306`
- **PHPMyAdmin**: Interfaccia web su `http://localhost:8080`

### Database Initialization

La cartella `db/` contiene:
- **`docker-compose.yml`**: Configurazione per avviare i servizi Docker
- **`01-init.sql`**: Script di inizializzazione che crea:
  - Schema database completo
  - Tutte le tabelle (utenti, targhe, parking_lot, reservation)
  - Indici per ottimizzazione prestazioni
  - Dati iniziali di test:
    - 1 Amministratore (admin@bresciagreen.it / password)
    - 1 Utente standard (mario.rossi@email.it / password)
    - 2 Targhe di test
    - 3 Parcheggi di Brescia

Lo script viene eseguito automaticamente al primo avvio di MySQL tramite Docker.

---

## 🗄️ Database

### Connessione

Connection.php implementa il **Singleton Pattern**:
- Una sola istanza PDO per tutta l'applicazione
- Connessione persistente tra le richieste
- Charset UTF-8MB4

### Tabelle Principali

#### `utenti`
```sql
CREATE TABLE utenti (
  id_utente UUID PRIMARY KEY,
  email VARCHAR(255) UNIQUE,
  password_hash VARCHAR(255),
  nome VARCHAR(100),
  cognome VARCHAR(100),
  ruolo ENUM('utente', 'admin') DEFAULT 'utente',
  telefono VARCHAR(20),
  data_nascita DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `targhe`
```sql
CREATE TABLE targhe (
  id_targa INT PRIMARY KEY AUTO_INCREMENT,
  id_utente UUID,
  targa VARCHAR(8) UNIQUE,
  selezionata CHAR(1) DEFAULT 'N',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_utente) REFERENCES utenti(id_utente)
);
```

#### `parking_lot`
```sql
CREATE TABLE parking_lot (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255),
  total_spots INT,
  address VARCHAR(255),
  lat FLOAT,
  lng FLOAT,
  hourly_rate DECIMAL(5,2),
  co2 INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `reservation`
```sql
CREATE TABLE reservation (
  id UUID PRIMARY KEY,
  parking_lot_id INT,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  license_plate VARCHAR(8),
  start_time DATETIME,
  end_time DATETIME,
  price DECIMAL(7,2),
  status ENUM('ACTIVE', 'COMPLETED', 'CANCELLED') DEFAULT 'ACTIVE',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parking_lot_id) REFERENCES parking_lot(id)
);
```

---

## 🔄 Middleware

### JwtMiddleware

- Valida il token JWT dal header `Authorization: Bearer <token>`
- Decodifica il payload e lo salva in `$request->getAttribute('jwt_payload')`
- Blocca le rotte protette con `401 Unauthorized` se il token è assente o scaduto

---

## 💡 Funzionalita Principali

### ✅ Implementate

1. **Registrazione e Login** con password hashata (Bcrypt)
2. **Autenticazione JWT** con scadenza 24h
3. **Gestione Profilo** (nome, cognome, telefono, data nascita)
4. **Gestione Targhe** (CRUD + selezione)
5. **CRUD Parcheggi** (admin only)
6. **Prenotazioni** con calcolo disponibilità in tempo reale
7. **Statistiche Occupazione** (admin only)
8. **Soft Delete** per prenotazioni (storico mantenuto)
9. **CORS** abilitato per comunicare con frontend

### 🔜 Possibili Estensioni

- Pagamento online (Stripe, PayPal)
- Notifiche email (PHPMailer)
- Sistema di rating
- Storico fatture
- Abonnamenti mensili
- Geolocalizzazione in tempo reale

---

## 🔒 Sicurezza

- ✅ Password hashate con Bcrypt
- ✅ Validazione JWT nei middleware
- ✅ Prepared statements (prevenzione SQL injection)
- ✅ Controllo ruoli (admin vs user)
- ✅ CORS configurato
- ⚠️ JWT_SECRET deve essere cambiato in produzione
- ⚠️ Credenziali database nel config.php (non committare!)

---

## 🚀 Come Avviare

### Opzione 1: Con Docker (Consigliato)

#### Prerequisiti
- Docker Desktop installato

#### Installazione

```bash
cd parcheggio_php/db
docker-compose up -d
cd ..
composer install
```

Il database viene inizializzato automaticamente con lo script `01-init.sql`.

#### Accesso
- **API**: http://localhost/parcheggio_php/
- **PHPMyAdmin**: http://localhost:8080 (user: `root`, password: `rootpassword`)

---

### Opzione 2: Locale (Manual)

#### Prerequisiti
- PHP 7.4+
- MySQL 5.7+
- Composer

#### Installazione

```bash
cd parcheggio_php
composer install
```

#### Configurazione
1. Modifica `conf/config.php` con i dati del tuo database
2. Crea il database e importa le tabelle:
   - Accedi a MySQL: `mysql -u root -p`
   - Importa lo schema: `source db/01-init.sql;`

#### Esecuzione
```bash
php -S localhost:8000
```

Accedi all'API su `http://localhost:8000/index.php`

---

## 📝 Esempio di Utilizzo

### 1. Registrazione
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "mario@example.com",
    "password": "secret123",
    "nome": "Mario",
    "cognome": "Rossi"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "username": "mario@example.com",
    "password": "secret123"
  }'
```
**Risposta**: `{"access_token": "eyJhbGc..."}`

### 3. Creare Prenotazione
```bash
curl -X POST http://localhost:8000/api/v1/reservations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token_jwt>" \
  -d '{
    "parking_lot_id": 1,
    "license_plate": "AB123CD",
    "start_time": "2026-05-03 10:00:00",
    "end_time": "2026-05-03 12:00:00",
    "price": 3.00
  }'
```

### 4. Ottenere Profilo Utente
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer <token_jwt>"
```

### 5. Aggiungere Targa
```bash
curl -X POST http://localhost:8000/api/v1/auth/license-plates \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token_jwt>" \
  -d '{
    "targa": "AB123CD"
  }'
```

### 6. Verificare Disponibilità Parcheggio
```bash
curl -X GET "http://localhost:8000/api/v1/parking-lots/1/availability?date=2026-05-03&time=10:00&duration=2"
```

---

## 🛠️ Dettagli Implementativi

### Repository Pattern

Ogni repository gestisce le operazioni su una specifica tabella:

- **UtenteRepository**: CRUD utenti, gestione targhe, aggiornamento profilo
- **ParkingLotRepository**: CRUD parcheggi, calcolo disponibilità, statistiche
- **ReservationRepository**: CRUD prenotazioni, aggiornamento stato automatico

### Sicurezza delle Query

Tutte le query utilizzano **prepared statements** con parametri named:

```php
$stmt = $pdo->prepare('SELECT * FROM utenti WHERE email = :email');
$stmt->execute(['email' => $email]);
```

Questo previene SQL injection anche in caso di input malevoli.

### Gestione Transazioni

Per operazioni critiche come la selezione targhe, vengono utilizzate transazioni:

```php
$pdo->beginTransaction();
// Updates...
$pdo->commit(); // o rollBack() in caso di errore
```

---

## 📌 Note Importanti

1. **Token JWT scade**: Dopo 24 ore, l'utente deve fare di nuovo il login
2. **Soft Delete Prenotazioni**: Le prenotazioni cancellate rimangono nel DB con status 'CANCELLED'
3. **Auto-cleanup Prenotazioni**: Le prenotazioni scadute cambiano automaticamente a 'COMPLETED'
4. **Unicità Targhe**: Una targa non può essere registrata due volte
5. **Prima Targa di Default**: La prima targa aggiunta è automaticamente selezionata
6. **Overlay Prenotazioni**: Due prenotazioni possono sovrapporsi, solo una ridurrà i posti disponibili

---

## 👤 Autore

Progetto scolastico - Scuola Superiore di Informatica

---

## 📄 Licenza

Uso interno - Non distribuire

