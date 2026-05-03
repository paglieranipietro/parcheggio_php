<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container as Container;

require '../vendor/autoload.php';

require 'Util/Connection.php';
require 'Model/UtenteRepository.php';
require 'Model/ParkingLotRepository.php';
require 'Model/ReservationRepository.php';

require 'Controller/AuthController.php';
require 'Controller/ParkingLotController.php';
require 'Controller/ReservationController.php';

require 'Middleware/JwtMiddleware.php';

use Controller\AuthController;
use Controller\ParkingLotController;
use Controller\ReservationController;
use Middleware\JwtMiddleware;

$config = require 'conf/config.php';
$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$app->setBasePath($basePath);

$app->addBodyParsingMiddleware();
$container->set('config', $config);

$app->post('/api/v1/auth/register', AuthController::class . ':register');
$app->post('/api/v1/auth/token', AuthController::class . ':login');
$app->get('/api/v1/parking-lots', ParkingLotController::class . ':listAll');

$app->group('/api/v1', function ($group) {
    // Autenticazione e Profilo
    $group->get('/auth/me', AuthController::class . ':me');
    $group->put('/auth/me', AuthController::class . ':updateProfile');

    // NUOVE ROTTE TARGHE (Richieste da AccountSettings.jsx)
    $group->get('/auth/license-plates', AuthController::class . ':getLicensePlates');
    $group->post('/auth/license-plates', AuthController::class . ':addLicensePlate');
    $group->delete('/auth/license-plates/{id}', AuthController::class . ':deleteLicensePlate');
    $group->put('/auth/license-plates/{id}/select', AuthController::class . ':selectLicensePlate');

    // Parcheggi
    $group->post('/parking-lots', ParkingLotController::class . ':create');
    $group->delete('/parking-lots/{id}', ParkingLotController::class . ':delete');
    $group->put('/parking-lots/{id}', ParkingLotController::class . ':update');
    $group->get('/parking-lots/{id}/availability', ParkingLotController::class . ':checkAvailability');
    $group->get('/parking-lots/{id}/available-spots', ParkingLotController::class . ':checkAvailability');
    $group->get('/parking-lots/{id}/stats', ParkingLotController::class . ':getStats');

    // Prenotazioni
    $group->post('/reservations', ReservationController::class . ':create');
    $group->get('/reservations/user', ReservationController::class . ':listByUser');
    $group->put('/reservations/{id}', ReservationController::class . ':update');
    $group->delete('/reservations/{id}', ReservationController::class . ':delete');
    $group->get('/reservations/{id}', ReservationController::class . ':getById');
})->add(new JwtMiddleware($config['JWT_SECRET']));

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->run();