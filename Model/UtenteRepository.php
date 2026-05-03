<?php
namespace Model;
use Util\Connection;
use PDO;

class UtenteRepository {
    private $config;

    public function __construct($config){
        $this->config = $config;
    }

    public function getUtenteByEmail(string $email) {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('SELECT * FROM utenti WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Novità: Ricerca per ID per aggiornare il profilo
    public function getUtenteById(string $id) {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('SELECT * FROM utenti WHERE id_utente = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUtente(string $id, string $email, string $hash, string $nome, string $cognome): bool {
        $pdo = Connection::getInstance($this->config);
        // Il ruolo di default 'utente' è ora gestito dal database, ma possiamo forzarlo per sicurezza
        $stmt = $pdo->prepare('
            INSERT INTO utenti (id_utente, email, password_hash, nome, cognome, ruolo) 
            VALUES (:id, :email, :hash, :nome, :cognome, "utente")
        ');
        return $stmt->execute([
            'id' => $id, 'email' => $email, 'hash' => $hash, 'nome' => $nome, 'cognome' => $cognome
        ]);
    }

    // NUOVO: Aggiorna profilo utente
    public function updateProfilo(string $id, string $nome, string $cognome, ?string $telefono, ?string $data_nascita): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('
            UPDATE utenti 
            SET nome = :nome, cognome = :cognome, telefono = :telefono, data_nascita = :data_nascita 
            WHERE id_utente = :id
        ');
        return $stmt->execute([
            'nome' => $nome,
            'cognome' => $cognome,
            'telefono' => $telefono ?: null,
            'data_nascita' => $data_nascita ?: null,
            'id' => $id
        ]);
    }

    // ==========================================
    // GESTIONE TARGHE
    // ==========================================

    public function getTargheByUser(string $id_utente): array {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('SELECT * FROM targhe WHERE id_utente = :id ORDER BY created_at DESC');
        $stmt->execute(['id' => $id_utente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addTarga(string $id_utente, string $targa) {
        $pdo = Connection::getInstance($this->config);

        // Se è la prima targa, impostala come selezionata di default
        $targhe_esistenti = $this->getTargheByUser($id_utente);
        $selezionata = count($targhe_esistenti) === 0 ? 'S' : 'N';

        $stmt = $pdo->prepare('INSERT INTO targhe (id_utente, targa, selezionata) VALUES (:id_utente, :targa, :selezionata)');
        $success = $stmt->execute(['id_utente' => $id_utente, 'targa' => $targa, 'selezionata' => $selezionata]);

        if ($success) {
            $id_targa = $pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT * FROM targhe WHERE id_targa = :id');
            $stmt->execute(['id' => $id_targa]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function deleteTarga(string $id_utente, int $id_targa): bool {
        $pdo = Connection::getInstance($this->config);
        // Controlliamo l'id_utente per sicurezza: un utente può cancellare SOLO le sue targhe
        $stmt = $pdo->prepare('DELETE FROM targhe WHERE id_targa = :id_targa AND id_utente = :id_utente');
        return $stmt->execute(['id_targa' => $id_targa, 'id_utente' => $id_utente]);
    }

    public function selectTarga(string $id_utente, int $id_targa): bool {
        $pdo = Connection::getInstance($this->config);
        try {
            $pdo->beginTransaction();
            // 1. Deseleziona tutte le targhe dell'utente
            $stmt1 = $pdo->prepare("UPDATE targhe SET selezionata = 'N' WHERE id_utente = :id_utente");
            $stmt1->execute(['id_utente' => $id_utente]);

            // 2. Seleziona solo quella richiesta
            $stmt2 = $pdo->prepare("UPDATE targhe SET selezionata = 'S' WHERE id_targa = :id_targa AND id_utente = :id_utente");
            $stmt2->execute(['id_targa' => $id_targa, 'id_utente' => $id_utente]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}