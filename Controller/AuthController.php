<?php
namespace Controller;

use Psr\Container\ContainerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Model\UtenteRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\Uuid;

class AuthController {
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        // Mette i dati in formato JSON e aggiunge l'intestazione corretta
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function register(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $repo = new UtenteRepository($this->container->get('config'));

        // Controlla se l'email è già usata
        if ($repo->getUtenteByEmail($data['email'])) {
            return $this->jsonResponse($response, ['error' => 'Email già registrata'], 400);
        }

        $id = Uuid::uuid7()->toString();
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        if ($repo->createUtente($id, $data['email'], $hash, $data['nome'], $data['cognome'])) {
            return $this->jsonResponse($response, [
                'id_utente' => $id, 'email' => $data['email'], 'nome' => $data['nome'], 'cognome' => $data['cognome'], 'ruolo' => 'utente'
            ], 201);
        }
        return $this->jsonResponse($response, ['error' => 'Errore server'], 500);
    }

    public function login(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $email = $data['username'] ?? $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $repo = new UtenteRepository($this->container->get('config'));
        $user = $repo->getUtenteByEmail($email);

        // Controlla email e password
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->jsonResponse($response, ['error' => 'Email o password non corretti'], 401);
        }

        $config = $this->container->get('config');
        $payload = [
            'iss' => "parking_system", 'sub' => $user['email'], 'role' => $user['ruolo'],
            'nome' => $user['nome'], 'cognome' => $user['cognome'], // Utili per trovare le prenotazioni dell'utente
            'iat' => time(), 'exp' => time() + (60 * 60 * 24) // Token valido per 24 ore
        ];

        $jwt = JWT::encode($payload, $config['JWT_SECRET'], 'HS256');
        return $this->jsonResponse($response, ['access_token' => $jwt, 'token_type' => 'bearer']);
    }

    public function me(Request $request, Response $response): Response {
        // Legge i dati dell'utente dal token che è stato verificato dal Middleware
        $jwt = $request->getAttribute('jwt_payload');

        $repo = new UtenteRepository($this->container->get('config'));
        $user = $repo->getUtenteByEmail($jwt->sub);

        if (!$user) return $this->jsonResponse($response, ['error' => 'Utente non trovato'], 404);
        unset($user['password_hash']); // Non mandare la password al client
        return $this->jsonResponse($response, $user);
    }
}