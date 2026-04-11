<?php
namespace Controller;

use Psr\Container\ContainerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Model\ReservationRepository;
use Ramsey\Uuid\Uuid;

class ReservationController {
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function create(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $repo = new ReservationRepository($this->container->get('config'));
        // Generiamo un ID unico per la prenotazione
        $id = Uuid::uuid7()->toString();

        if ($repo->createReservation($id, $data['parking_lot_id'], $data['first_name'], $data['last_name'], $data['license_plate'], $data['start_time'], $data['end_time'])) {
            $response->getBody()->write(json_encode(['message' => 'Prenotazione creata', 'reservation_id' => $id]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        }
        $response->getBody()->write(json_encode(['error' => 'Errore nella creazione']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    public function listByUser(Request $request, Response $response): Response {
        // Prende i dati dell'utente dal token (non dall'URL per motivi di sicurezza)
        $jwt = $request->getAttribute('jwt_payload');

        $repo = new ReservationRepository($this->container->get('config'));
        // Usa il nome e cognome che sono stati messi nel token al login
        $reservations = $repo->getReservationsByUser($jwt->nome, $jwt->cognome);

        $response->getBody()->write(json_encode($reservations));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}