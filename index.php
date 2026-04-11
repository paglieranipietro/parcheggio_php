<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container as Container;
require 'Middleware/JwtMiddleware.php';
use Middleware\JwtMiddleware;

// Autoload
require '../vendor/autoload.php';

// Model & Util
require 'Util/Connection.php';
require 'Model/UtenteRepository.php';
require 'Model/ParkingLotRepository.php';
require 'Model/ReservationRepository.php';

// Controller
require 'Controller/AuthController.php';
require 'Controller/ParkingLotController.php';
require 'Controller/ReservationController.php';

use Controller\AuthController;
use Controller\ParkingLotController;
use Controller\ReservationController;

// Configurazione
$config = require 'conf/config.php';
$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->setBasePath($config['BASEPATH']);
$app->addBodyParsingMiddleware();
$container->set('config', $config);


// ROTTE CHE CHIUNQUE PUÒ USARE (senza login)
$app->post('/api/v1/auth/register', AuthController::class . ':register');
$app->post('/api/v1/auth/token', AuthController::class . ':login');
// I parcheggi si vedono per tutti per mostrarli sulla mappa
$app->get('/api/v1/parking-lots', ParkingLotController::class . ':listAll');

// ROTTE PROTETTE (serve il token per accedere)
$app->group('/api/v1', function ($group) {
    // Informazioni dell'utente loggato
    $group->get('/auth/me', AuthController::class . ':me');

    // Creare e eliminare parcheggi
    $group->post('/parking-lots', ParkingLotController::class . ':create');
    $group->delete('/parking-lots/{id}', ParkingLotController::class . ':delete');

    // Creare prenotazioni e vedere le tue prenotazioni
    $group->post('/reservations', ReservationController::class . ':create');
    $group->get('/reservations/user', ReservationController::class . ':listByUser');

// Controlla il token JWT per tutte le rotte di questo gruppo
})->add(new JwtMiddleware($config['JWT_SECRET']));

$app->run();