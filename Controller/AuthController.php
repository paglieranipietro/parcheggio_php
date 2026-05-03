<?php
namespace Controller;

use Psr\Container\ContainerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Model\UtenteRepository;
use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;

class AuthController {
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function register(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $repo = new UtenteRepository($this->container->get('config'));

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

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->jsonResponse($response, ['error' => 'Email o password non corretti'], 401);
        }

        $config = $this->container->get('config');
        $payload = [
            'iss' => "parking_system",
            'sub' => $user['email'],
            'id_utente' => $user['id_utente'], // CRITICO: Aggiunto id_utente nel token per le query!
            'role' => $user['ruolo'],
            'nome' => $user['nome'],
            'cognome' => $user['cognome'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24)
        ];

        $jwt = JWT::encode($payload, $config['JWT_SECRET'], 'HS256');
        return $this->jsonResponse($response, ['access_token' => $jwt, 'token_type' => 'bearer']);
    }

    public function me(Request $request, Response $response): Response {
        $jwt = $request->getAttribute('jwt_payload');
        $repo = new UtenteRepository($this->container->get('config'));
        $user = $repo->getUtenteById($jwt->id_utente);

        if (!$user) return $this->jsonResponse($response, ['error' => 'Utente non trovato'], 404);

        unset($user['password_hash']);
        return $this->jsonResponse($response, $user);
    }

    // ==========================================
    // NUOVI METODI: TARGHE E PROFILO
    // ==========================================

    public function getLicensePlates(Request $request, Response $response): Response {
        $jwt = $request->getAttribute('jwt_payload');
        $repo = new UtenteRepository($this->container->get('config'));

        $targhe = $repo->getTargheByUser($jwt->id_utente);
        return $this->jsonResponse($response, $targhe);
    }

    public function addLicensePlate(Request $request, Response $response): Response {
        $jwt = $request->getAttribute('jwt_payload');
        $data = $request->getParsedBody();
        $targa = strtoupper(trim($data['targa'] ?? ''));

        if (!$targa || !preg_match('/^[A-Z]{2}\d{3}[A-Z]{2}$/', $targa)) {
            return $this->jsonResponse($response, ['error' => 'Formato targa non valido'], 400);
        }

        $repo = new UtenteRepository($this->container->get('config'));
        $nuova_targa = $repo->addTarga($jwt->id_utente, $targa);

        if ($nuova_targa) {
            return $this->jsonResponse($response, $nuova_targa, 201);
        }
        return $this->jsonResponse($response, ['error' => 'Errore o targa già esistente'], 500);
    }

    public function deleteLicensePlate(Request $request, Response $response, array $args): Response {
        $jwt = $request->getAttribute('jwt_payload');
        $id_targa = (int)$args['id'];

        $repo = new UtenteRepository($this->container->get('config'));
        if ($repo->deleteTarga($jwt->id_utente, $id_targa)) {
            return $this->jsonResponse($response, ['message' => 'Targa eliminata']);
        }
        return $this->jsonResponse($response, ['error' => 'Errore cancellazione'], 500);
    }

    public function selectLicensePlate(Request $request, Response $response, array $args): Response {
        $jwt = $request->getAttribute('jwt_payload');
        $id_targa = (int)$args['id'];

        $repo = new UtenteRepository($this->container->get('config'));
        if ($repo->selectTarga($jwt->id_utente, $id_targa)) {
            return $this->jsonResponse($response, ['message' => 'Targa selezionata']);
        }
        return $this->jsonResponse($response, ['error' => 'Errore selezione'], 500);
    }

    public function updateProfile(Request $request, Response $response): Response {
        $jwt = $request->getAttribute('jwt_payload');
        $data = $request->getParsedBody();

        $nome = $data['nome'] ?? '';
        $cognome = $data['cognome'] ?? '';
        $telefono = $data['telefono'] ?? null;
        $data_nascita = $data['data_nascita'] ?? null;

        $repo = new UtenteRepository($this->container->get('config'));

        if ($repo->updateProfilo($jwt->id_utente, $nome, $cognome, $telefono, $data_nascita)) {
            return $this->jsonResponse($response, ['message' => 'Profilo aggiornato con successo']);
        }

        return $this->jsonResponse($response, ['error' => 'Errore durante l\'aggiornamento'], 500);
    }
}