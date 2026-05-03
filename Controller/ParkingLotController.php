<?php
namespace Controller;

use Psr\Container\ContainerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Model\ParkingLotRepository;

class ParkingLotController {
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function listAll(Request $request, Response $response): Response {
        $repo = new ParkingLotRepository($this->container->get('config'));
        return $this->jsonResponse($response, $repo->getAllParkingLots());
    }

    public function create(Request $request, Response $response): Response {
        // Controllo Sicurezza: Solo Admin!
        $jwt = $request->getAttribute('jwt_payload');
        if ($jwt->role !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Accesso negato. Solo amministratori.'], 403);
        }

        $data = $request->getParsedBody();
        $repo = new ParkingLotRepository($this->container->get('config'));

        $name = $data['name'] ?? '';
        $total_spots = (int)($data['total_spots'] ?? 0);
        $address = $data['address'] ?? 'Indirizzo non specificato';
        $lat = (float)($data['lat'] ?? 45.5415);
        $lng = (float)($data['lng'] ?? 10.2160);
        $hourly_rate = (float)($data['hourly_rate'] ?? 1.00);
        $co2 = (int)($data['co2'] ?? 100);

        if ($repo->createParkingLot($name, $total_spots, $address, $lat, $lng, $hourly_rate, $co2)) {
            return $this->jsonResponse($response, ['message' => 'Parcheggio creato'], 201);
        }
        return $this->jsonResponse($response, ['error' => 'Errore server'], 500);
    }

    public function update(Request $request, Response $response, array $args): Response {
        // Controllo Sicurezza: Solo Admin!
        $jwt = $request->getAttribute('jwt_payload');
        if ($jwt->role !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Accesso negato. Solo amministratori.'], 403);
        }

        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $repo = new ParkingLotRepository($this->container->get('config'));

        $name = $data['name'] ?? '';
        $total_spots = (int)($data['total_spots'] ?? 0);
        $address = $data['address'] ?? '';
        $lat = (float)($data['lat'] ?? 45.5415);
        $lng = (float)($data['lng'] ?? 10.2160);
        $hourly_rate = (float)($data['hourly_rate'] ?? 1.00);
        $co2 = (int)($data['co2'] ?? 100);

        if ($repo->updateParkingLot($id, $name, $total_spots, $address, $lat, $lng, $hourly_rate, $co2)) {
            return $this->jsonResponse($response, ['message' => 'Parcheggio aggiornato con successo']);
        }
        return $this->jsonResponse($response, ['error' => 'Errore server durante l\'aggiornamento'], 500);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        // Controllo Sicurezza: Solo Admin!
        $jwt = $request->getAttribute('jwt_payload');
        if ($jwt->role !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Accesso negato. Solo amministratori.'], 403);
        }

        $repo = new ParkingLotRepository($this->container->get('config'));
        if ($repo->deleteParkingLot((int)$args['id'])) {
            return $this->jsonResponse($response, ['message' => 'Parcheggio eliminato']);
        }
        return $this->jsonResponse($response, ['error' => 'Errore server'], 500);
    }

    // Risponde al polling del BookingForm.jsx
    public function checkAvailability(Request $request, Response $response, array $args): Response {
        $parking_id = (int)$args['id'];
        $params = $request->getQueryParams();

        $date = $params['date'] ?? null;
        $time = $params['time'] ?? '00:00';
        $duration = (float)($params['duration'] ?? 1);

        if (!$date) {
            return $this->jsonResponse($response, ['error' => 'Data mancante'], 400);
        }

        $start_time = date('Y-m-d H:i:s', strtotime("$date $time"));
        // Calcola end_time aggiungendo le ore (duration)
        $end_time = date('Y-m-d H:i:s', strtotime("$start_time + " . ($duration * 60) . " minutes"));

        $repo = new ParkingLotRepository($this->container->get('config'));
        $availability = $repo->getAvailableSpots($parking_id, $start_time, $end_time);

        return $this->jsonResponse($response, $availability);
    }

    public function getStats(Request $request, Response $response, array $args): Response {
        // Controllo Sicurezza: Solo Admin!
        $jwt = $request->getAttribute('jwt_payload');
        if ($jwt->role !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Non autorizzato'], 403);
        }

        $repo = new ParkingLotRepository($this->container->get('config'));
        $stats = $repo->getParkingStats((int)$args['id']);

        return $this->jsonResponse($response, $stats);
    }
}