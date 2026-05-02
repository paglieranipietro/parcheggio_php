<?php
namespace Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Model\ReservationRepository;
use Ramsey\Uuid\Uuid;

class ReservationController {
    private $config;

    public function __construct(ContainerInterface $container) {
        $this->config = $container->get('config');
    }

    public function create(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('jwt_payload');
        $id = Uuid::uuid4()->toString();
        $repo = new ReservationRepository($this->config);

        $success = $repo->createReservation(
            $id,
            $data['parking_lot_id'],
            $user->nome,
            $user->cognome,
            $data['license_plate'],
            $data['start_time'],
            $data['end_time']
        );

        if ($success) {
            $response->getBody()->write(json_encode(['message' => 'Prenotazione creata con successo']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        }

        $response->getBody()->write(json_encode(['error' => 'Errore nella creazione della prenotazione']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    public function listByUser(Request $request, Response $response): Response {
        $user = $request->getAttribute('jwt_payload');
        $repo = new ReservationRepository($this->config);
        $reservations = $repo->getReservationsByUser($user->nome, $user->cognome);

        $response->getBody()->write(json_encode($reservations));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $repo = new ReservationRepository($this->config);

        $success = $repo->updateReservation(
            $id,
            $data['parking_lot_id'],
            $data['license_plate'],
            $data['start_time'],
            $data['end_time']
        );

        if ($success) {
            $response->getBody()->write(json_encode(['message' => 'Prenotazione aggiornata']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $response->getBody()->write(json_encode(['error' => 'Errore aggiornamento']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $repo = new ReservationRepository($this->config);

        if ($repo->deleteReservation($id)) {
            $response->getBody()->write(json_encode(['message' => 'Prenotazione cancellata']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $response->getBody()->write(json_encode(['error' => 'Errore cancellazione']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}