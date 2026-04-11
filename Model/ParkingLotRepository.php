<?php
namespace Model;
use Util\Connection;
use PDO;

class ParkingLotRepository {
    private $config;

    public function __construct($config){
        $this->config = $config;
    }

    public function getAllParkingLots(): array {
        // Prende tutti i parcheggi dal database
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->query('SELECT * FROM parking_lot');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createParkingLot(string $name, int $total_spots): bool {
        // Crea un nuovo parcheggio nel database
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('INSERT INTO parking_lot (name, total_spots) VALUES (:name, :total_spots)');
        return $stmt->execute(['name' => $name, 'total_spots' => $total_spots]);
    }

    public function deleteParkingLot(int $id): bool {
        // Elimina un parcheggio dal database
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('DELETE FROM parking_lot WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}