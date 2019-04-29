<?php

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Elevators\Container;
use Elevators\Database\Configuration;
use Elevators\Database\Elevator;
use Elevators\Database\Order;
use Elevators\Database\PostgresConnection;
use Elevators\Database\Statistics;

require_once ('vendor/autoload.php');

define('APP_ROOT', __DIR__);

$dotenv = Dotenv::create(APP_ROOT, '.env');
$dotenv->load();
$dotenv->required([
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASSWORD',
    'RABBITMQ_DEFAULT_USER',
    'RABBITMQ_DEFAULT_PASS',
    'WS_HOST'
]);

define('WS_HOST', getenv('WS_HOST'));

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'connection.postgres' => function () {
        return new PostgresConnection(
            getenv('DB_HOST'),
            getenv('DB_NAME'),
            getenv('DB_USER'),
            getenv('DB_PASSWORD')
        );
    },
    'connection.rabbitmq' => function () {
        return [
            'host' => 'rabbitmq',
            'user' => getenv('RABBITMQ_DEFAULT_USER'),
            'password' => getenv('RABBITMQ_DEFAULT_PASS'),
        ];
    },
    'database.configuration' => DI\create(Configuration::class)
        ->constructor(DI\get('connection.postgres')),
    'database.elevator' => DI\create(Elevator::class)
        ->constructor(DI\get('connection.postgres')),
    'database.order' => DI\create(Order::class)
        ->constructor(DI\get('connection.postgres')),
    'database.statistics' => DI\create(Statistics::class)
        ->constructor(DI\get('connection.postgres')),
]);
Container::setContiner($containerBuilder->build());
