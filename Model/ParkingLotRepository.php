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

        $stmt = $pdo->query("
            SELECT p.*,
                   (p.total_spots - COALESCE((
                       SELECT COUNT(*)
                       FROM reservation r
                       WHERE r.parking_lot_id = p.id
                         AND r.status = 'ACTIVE'
                         AND r.start_time <= NOW()
                         AND r.end_time >= NOW()
                   ), 0)) AS available_spots
            FROM parking_lot p
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createParkingLot(string $name, int $total_spots, string $address, float $lat, float $lng, float $hourly_rate, int $co2): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('
            INSERT INTO parking_lot (name, total_spots, address, lat, lng, hourly_rate, co2) 
            VALUES (:name, :total_spots, :address, :lat, :lng, :hourly_rate, :co2)
        ');
        return $stmt->execute([
            'name' => $name, 'total_spots' => $total_spots, 'address' => $address,
            'lat' => $lat, 'lng' => $lng, 'hourly_rate' => $hourly_rate, 'co2' => $co2
        ]);
    }

    public function deleteParkingLot(int $id): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('DELETE FROM parking_lot WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ==========================================
    // LOGICA CORE: CALCOLO POSTI DISPONIBILI
    // ==========================================
    public function getAvailableSpots(int $parking_lot_id, string $req_start_time, string $req_end_time): array {
        $pdo = Connection::getInstance($this->config);

        // 1. Recupera i posti totali del parcheggio
        $stmt = $pdo->prepare('SELECT total_spots FROM parking_lot WHERE id = :id');
        $stmt->execute(['id' => $parking_lot_id]);
        $parking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$parking) {
            return ['available' => false, 'spots' => 0, 'error' => 'Parcheggio inesistente'];
        }
        $total_spots = (int)$parking['total_spots'];

        // 2. Conta le prenotazioni ATTIVE che si sovrappongono all'orario richiesto
        // Una prenotazione si sovrappone se: Inizio_Esistente < Fine_Richiesta AND Fine_Esistente > Inizio_Richiesta
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as occupied_spots 
            FROM reservation 
            WHERE parking_lot_id = :parking_id 
              AND status = 'ACTIVE' 
              AND (start_time < :end_time AND end_time > :start_time)
        ");
        $stmt->execute([
            'parking_id' => $parking_lot_id,
            'start_time' => $req_start_time,
            'end_time' => $req_end_time
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $occupied_spots = (int)$result['occupied_spots'];
        $free_spots = $total_spots - $occupied_spots;

        return [
            'available' => $free_spots > 0,
            'spots' => max(0, $free_spots),
            'total_spots' => $total_spots
        ];
    }

    // ==========================================
    // STATISTICHE ADMIN
    // ==========================================
    public function getParkingStats(int $parking_lot_id): array {
        $pdo = Connection::getInstance($this->config);

        // Prendiamo il totale
        $stmt = $pdo->prepare('SELECT total_spots FROM parking_lot WHERE id = :id');
        $stmt->execute(['id' => $parking_lot_id]);
        $parking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$parking) return ['total_spots' => 0, 'occupied_spots' => 0];

        // Usiamo la logica esatta: conta le prenotazioni che COPRONO il momento attuale
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as occupied 
            FROM reservation 
            WHERE parking_lot_id = :id 
              AND status = 'ACTIVE' 
              AND start_time <= NOW() 
              AND end_time >= NOW()
        ");
        $stmt->execute(['id' => $parking_lot_id]);
        $occ = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_spots' => (int)$parking['total_spots'],
            'occupied_spots' => (int)$occ['occupied']
        ];
    }

    public function updateParkingLot(int $id, string $name, int $total_spots, string $address, float $lat, float $lng, float $hourly_rate, int $co2): bool {
        $pdo = Connection::getInstance($this->config);
        $stmt = $pdo->prepare('
            UPDATE parking_lot 
            SET name = :name, total_spots = :total_spots, address = :address, 
                lat = :lat, lng = :lng, hourly_rate = :hourly_rate, co2 = :co2
            WHERE id = :id
        ');
        return $stmt->execute([
            'id' => $id,
            'name' => $name,
            'total_spots' => $total_spots,
            'address' => $address,
            'lat' => $lat,
            'lng' => $lng,
            'hourly_rate' => $hourly_rate,
            'co2' => $co2
        ]);
    }
}