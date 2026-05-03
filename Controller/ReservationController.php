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

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function create(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('jwt_payload');
        $id = Uuid::uuid4()->toString();
        $repo = new ReservationRepository($this->config);

        $price = isset($data['price']) ? (float)$data['price'] : 0.00;

        $success = $repo->createReservation(
            $id, $data['parking_lot_id'], $user->nome, $user->cognome,
            $data['license_plate'], $data['start_time'], $data['end_time'], $price
        );

        if ($success) {
            return $this->jsonResponse($response, ['message' => 'Prenotazione creata con successo'], 201);
        }
        return $this->jsonResponse($response, ['error' => 'Errore nella creazione'], 500);
    }

    public function listByUser(Request $request, Response $response): Response {
        $user = $request->getAttribute('jwt_payload');
        $repo = new ReservationRepository($this->config);
        return $this->jsonResponse($response, $repo->getReservationsByUser($user->nome, $user->cognome));
    }

    public function getById(Request $request, Response $response, array $args): Response {
        $repo = new ReservationRepository($this->config);
        $reservation = $repo->getReservationById($args['id']);
        if ($reservation) {
            return $this->jsonResponse($response, $reservation);
        }
        return $this->jsonResponse($response, ['error' => 'Non trovata'], 404);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $repo = new ReservationRepository($this->config);

        $price = isset($data['price']) ? (float)$data['price'] : 0.00;

        $success = $repo->updateReservation(
            $id, $data['parking_lot_id'], $data['license_plate'],
            $data['start_time'], $data['end_time'], $price
        );

        if ($success) {
            return $this->jsonResponse($response, ['message' => 'Prenotazione aggiornata']);
        }
        return $this->jsonResponse($response, ['error' => 'Errore aggiornamento'], 500);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $repo = new ReservationRepository($this->config);

        if ($repo->deleteReservation($id)) {
            return $this->jsonResponse($response, ['message' => 'Prenotazione cancellata']);
        }
        return $this->jsonResponse($response, ['error' => 'Errore cancellazione'], 500);
    }
}