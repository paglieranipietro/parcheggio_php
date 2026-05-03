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

    public function listAll(Request $request, Response $response): Response {
        $repo = new ParkingLotRepository($this->container->get('config'));
        $response->getBody()->write(json_encode($repo->getAllParkingLots()));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function create(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $repo = new ParkingLotRepository($this->container->get('config'));

        // Assicurati di estrarre e passare tutti i parametri richiesti, fornendo dei default se mancano
        $name = $data['name'] ?? '';
        $total_spots = (int)($data['total_spots'] ?? 0);
        $address = $data['address'] ?? '';
        $lat = (float)($data['lat'] ?? 0.0);
        $lng = (float)($data['lng'] ?? 0.0);
        $hourly_rate = (float)($data['hourly_rate'] ?? 0.0);
        $co2 = (int)($data['co2'] ?? 0);

        if ($repo->createParkingLot($name, $total_spots, $address, $lat, $lng, $hourly_rate, $co2)) {
            $response->getBody()->write(json_encode(['message' => 'Parcheggio creato']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        }
        $response->getBody()->write(json_encode(['error' => 'Errore durante la creazione']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $repo = new ParkingLotRepository($this->container->get('config'));
        if ($repo->deleteParkingLot((int)$args['id'])) {
            $response->getBody()->write(json_encode(['message' => 'Parcheggio eliminato']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $response->getBody()->write(json_encode(['error' => 'Errore durante l\'eliminazione']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}