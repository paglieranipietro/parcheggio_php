<?php

//Il namespace deve essere uguale alla cartella che contiene il file
namespace Util;
use PDO;


/**
 * Classe per gestire la connessione al database
 */

class Connection
{
    //Statico perchè è un attributo di classe istanziato una sola volta
    private static ?PDO $pdo = null; // Forza il Singleton reale


    /**
     * Costruttore privato per evitare la creazione di oggetti
     */
    private function __construct()
    {

    }

    public static function getInstance($config): PDO
    {
        if (self::$pdo === null) {
            $DSN = 'mysql:host=' . $config['DB_HOST'] . ';dbname=' . $config['DB_NAME'] . ';charset=utf8mb4';
            self::$pdo = new PDO($DSN, $config['DB_USER'], $config['DB_PASS'], [
                PDO::ATTR_PERSISTENT => true, // Mantiene la connessione aperta tra le richieste
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

        }
        return self::$pdo;
    }
}

