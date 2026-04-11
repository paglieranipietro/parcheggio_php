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
        // Recupera e restituisce tutti i parcheggi dal database
        $repo = new ParkingLotRepository($this->container->get('config'));
        $response->getBody()->write(json_encode($repo->getAllParkingLots()));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function create(Request $request, Response $response): Response {
        // Crea un nuovo parcheggio
        $data = $request->getParsedBody();
        $repo = new ParkingLotRepository($this->container->get('config'));

        if ($repo->createParkingLot($data['name'], $data['total_spots'])) {
            $response->getBody()->write(json_encode(['message' => 'Parcheggio creato']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        }
        $response->getBody()->write(json_encode(['error' => 'Errore']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        // Elimina un parcheggio dal database
        $repo = new ParkingLotRepository($this->container->get('config'));
        if ($repo->deleteParkingLot((int)$args['id'])) {
            $response->getBody()->write(json_encode(['message' => 'Parcheggio eliminato']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $response->getBody()->write(json_encode(['error' => 'Errore']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}