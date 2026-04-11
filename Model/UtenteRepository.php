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
        // Cerca un utente tramite la sua email
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('SELECT * FROM utenti WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUtente(string $id, string $email, string $hash, string $nome, string $cognome): bool {
        // Crea un nuovo utente nel database con i dati ricevuti
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('
            INSERT INTO utenti (id_utente, email, password_hash, nome, cognome, ruolo) 
            VALUES (:id, :email, :hash, :nome, :cognome, "utente")
        ');
        return $stmt->execute([
            'id' => $id, 'email' => $email, 'hash' => $hash, 'nome' => $nome, 'cognome' => $cognome
        ]);
    }
}