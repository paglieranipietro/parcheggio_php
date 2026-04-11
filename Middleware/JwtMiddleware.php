<?php
namespace Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware {
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        $header = $request->getHeaderLine('Authorization');

        // Controlla se il token è presente nel header Authorization
        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Autenticazione richiesta']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $token = $matches[1];
            // Decodifica e verifica il token JWT
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

            // Salva il contenuto del token nella richiesta
            // Così i Controller possono leggerlo senza doverlo decodificare di nuovo
            $request = $request->withAttribute('jwt_payload', $decoded);

        } catch (\Exception $e) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Token non valido o scaduto']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Token ok, lascia passare la richiesta al Controller
        return $handler->handle($request);
    }
}