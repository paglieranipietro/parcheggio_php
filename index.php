<?php

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
    $group->get('/auth/me', AuthController::class . ':me');
    $group->post('/parking-lots', ParkingLotController::class . ':create');
    $group->delete('/parking-lots/{id}', ParkingLotController::class . ':delete');

    $group->post('/reservations', ReservationController::class . ':create');
    $group->get('/reservations/user', ReservationController::class . ':listByUser');
    $group->put('/reservations/{id}', ReservationController::class . ':update');
    $group->delete('/reservations/{id}', ReservationController::class . ':delete');
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