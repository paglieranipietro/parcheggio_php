<?php
namespace Model;
use Util\Connection;
use PDO;

class ReservationRepository {
    private $config;

    public function __construct($config){
        $this->config = $config;
    }

    public function createReservation(string $id, int $parking_lot_id, string $first_name, string $last_name, string $license_plate, string $start_time, string $end_time, float $price): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('
            INSERT INTO reservation (id, parking_lot_id, first_name, last_name, license_plate, start_time, end_time, price, status) 
            VALUES (:id, :parking_lot_id, :first_name, :last_name, :license_plate, :start_time, :end_time, :price, "ACTIVE")
        ');
        return $stmt->execute([
            'id' => $id, 'parking_lot_id' => $parking_lot_id, 'first_name' => $first_name,
            'last_name' => $last_name, 'license_plate' => $license_plate,
            'start_time' => $start_time, 'end_time' => $end_time, 'price' => $price
        ]);
    }

    public function getReservationsByUser(string $first_name, string $last_name): array {
        $pdo = Connection::getInstance($this->config);
        // Aggiorna lo stato a SCADUTA se il tempo è passato (pulizia automatica in lettura)
        $pdo->query("UPDATE reservation SET status = 'COMPLETED' WHERE end_time < NOW() AND status = 'ACTIVE'");

        $stmt = $pdo->prepare('
            SELECT r.*, p.name as parking_lot_name 
            FROM reservation r 
            JOIN parking_lot p ON r.parking_lot_id = p.id 
            WHERE r.first_name = :first_name AND r.last_name = :last_name
            ORDER BY r.created_at DESC
        ');
        $stmt->execute(['first_name' => $first_name, 'last_name' => $last_name]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReservationById(string $id) {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('SELECT * FROM reservation WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateReservation(string $id, int $parking_lot_id, string $license_plate, string $start_time, string $end_time, float $price): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('
            UPDATE reservation 
            SET parking_lot_id = :parking_lot_id, license_plate = :license_plate, start_time = :start_time, end_time = :end_time, price = :price
            WHERE id = :id
        ');
        return $stmt->execute([
            'id' => $id, 'parking_lot_id' => $parking_lot_id, 'license_plate' => $license_plate,
            'start_time' => $start_time, 'end_time' => $end_time, 'price' => $price
        ]);
    }

    // SOFT DELETE: Mantiene lo storico per l'admin ma la segna come cancellata
    public function deleteReservation(string $id): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare("UPDATE reservation SET status = 'CANCELLED' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}