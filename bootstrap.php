<?php

require_once ('vendor/autoload.php');

define('APP_ROOT', __DIR__);

$dotenv = \Dotenv\Dotenv::create(__DIR__, '.env');
$dotenv->load();
$dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'RABBITMQ_DEFAULT_USER', 'RABBITMQ_DEFAULT_PASS', 'WS_HOST']);

define('WS_HOST', getenv('WS_HOST'));
