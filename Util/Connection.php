<?php

// Il namespace deve essere uguale al nome della cartella
namespace Util;
use PDO;

// Classe che gestisce la connessione al database
// Usa il pattern Singleton (una sola connessione per tutta l'app)
class Connection
{
    // Attributo statico perché appartiene alla classe, non all'oggetto
    private static PDO $pdo;

    // Costruttore privato per evitare di creare oggetti di questa classe
    private function __construct()
    {

    }

    public static function getInstance($config): PDO
    {
        // Se la connessione non esiste ancora, la crea
        if (!isset($pdo)) {
            $DSN = 'mysql:host=' . $config['DB_HOST'] . ';dbname=' . $config['DB_NAME'];
            $pdo = new PDO($DSN, $config['DB_USER'], $config['DB_PASS']);
            // Imposta il modo di restituzione dei dati come array associativo
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return $pdo;
    }
}

