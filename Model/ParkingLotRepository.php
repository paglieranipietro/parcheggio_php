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
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->query('SELECT * FROM parking_lot');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createParkingLot(string $name, int $total_spots, string $address, float $lat, float $lng, float $hourly_rate, int $co2): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('
            INSERT INTO parking_lot (name, total_spots, address, lat, lng, hourly_rate, co2) 
            VALUES (:name, :total_spots, :address, :lat, :lng, :hourly_rate, :co2)
        ');
        return $stmt->execute([
            'name' => $name,
            'total_spots' => $total_spots,
            'address' => $address,
            'lat' => $lat,
            'lng' => $lng,
            'hourly_rate' => $hourly_rate,
            'co2' => $co2
        ]);
    }

    public function deleteParkingLot(int $id): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('DELETE FROM parking_lot WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}