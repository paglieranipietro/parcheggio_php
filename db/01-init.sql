-- Creazione del database (modifica il nome se nel tuo docker-compose è diverso)
CREATE DATABASE IF NOT EXISTS Parcheggio;
USE Parcheggio;

-- --------------------------------------------------------
-- 1. TABELLA UTENTI (Aggiornata con Telefono, Data di Nascita e Ruoli)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS utenti (
    id_utente VARCHAR(36) PRIMARY KEY, -- Usiamo VARCHAR(36) per gli UUID generati dal backend
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,    -- NUOVO CAMPO
    data_nascita DATE DEFAULT NULL,       -- NUOVO CAMPO
    ruolo ENUM('utente', 'admin') DEFAULT 'utente', -- GESTIONE RUOLI NATIVA
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- 2. TABELLA TARGHE (Nuova Tabella Relazionale)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS targhe (
    id_targa INT AUTO_INCREMENT PRIMARY KEY,
    id_utente VARCHAR(36) NOT NULL,
    targa VARCHAR(10) NOT NULL,
    selezionata ENUM('S', 'N') DEFAULT 'N',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utente) REFERENCES utenti(id_utente) ON DELETE CASCADE
    -- ON DELETE CASCADE: se elimini l'utente, spariscono anche le sue targhe
);

-- --------------------------------------------------------
-- 3. TABELLA PARCHEGGI (Struttura completa per l'Admin e Mappa)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS parking_lot (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    total_spots INT NOT NULL,
    address VARCHAR(255) NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    hourly_rate DECIMAL(5, 2) NOT NULL,
    co2 INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- 4. TABELLA PRENOTAZIONI (Aggiornata con Prezzo e Soft Delete)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservation (
    id VARCHAR(36) PRIMARY KEY,
    parking_lot_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    license_plate VARCHAR(10) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    price DECIMAL(8, 2) NOT NULL DEFAULT 0.00, -- NUOVO CAMPO
    status ENUM('ACTIVE', 'CANCELLED', 'COMPLETED') DEFAULT 'ACTIVE', -- STATI VERI
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parking_lot_id) REFERENCES parking_lot(id) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- 5. OTTIMIZZAZIONE PRESTAZIONI (Indici)
-- --------------------------------------------------------

-- Velocizza il calcolo della disponibilità (Blocco 3) e il polling della dashboard[cite: 6, 31]
-- Copre la query: status = 'ACTIVE' AND (start_time < end AND end_time > start)
CREATE INDEX idx_reservation_status_time ON reservation (status, start_time, end_time);

-- Velocizza il caricamento della lista prenotazioni utente (Blocco 2)[cite: 6, 18]
CREATE INDEX idx_reservation_user ON reservation (first_name, last_name);

-- Velocizza il caricamento del "garage" targhe nelle impostazioni (Blocco 2)[cite: 7, 16]
CREATE INDEX idx_targhe_utente ON targhe (id_utente);

-- Velocizza la ricerca dei parcheggi per nome o indirizzo
CREATE INDEX idx_parking_lot_info ON parking_lot (name, address);

-- ========================================================
-- POPOLAMENTO DATI INIZIALI (Seeders)
-- ========================================================

-- Inserimento di un Amministratore e di un Utente standard.
-- N.B. La password per entrambi è "password" (l'hash bcrypt corrisponde a "password")
INSERT INTO utenti (id_utente, email, password_hash, nome, cognome, ruolo) VALUES 
('018e7b45-1c2d-7a8b-9c0d-1e2f3a4b5c6d', 'admin@bresciagreen.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Admin', 'admin'),
('018e7b45-2d3e-8b9c-ad1e-2f3g4h5i6j7k', 'mario.rossi@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', 'utente');

-- Inserimento di alcune targhe per Mario Rossi
INSERT INTO targhe (id_utente, targa, selezionata) VALUES 
('018e7b45-2d3e-8b9c-ad1e-2f3g4h5i6j7k', 'AB123CD', 'S'),
('018e7b45-2d3e-8b9c-ad1e-2f3g4h5i6j7k', 'EF456GH', 'N');

-- Inserimento Parcheggi Iniziali (Coordinate reali di Brescia per far funzionare la Mappa)
INSERT INTO parking_lot (name, total_spots, address, lat, lng, hourly_rate, co2) VALUES 
('Parcheggio Vittoria', 150, 'Piazza della Vittoria, Brescia', 45.539820, 10.220550, 2.50, 120),
('Parcheggio Fossa Bagni', 200, 'Piazzale Cesare Battisti, Brescia', 45.543180, 10.221760, 1.80, 80),
('Parcheggio Stazione', 350, 'Viale della Stazione, Brescia', 45.533250, 10.213450, 1.50, 150);