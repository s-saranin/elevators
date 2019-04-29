<?php

use Elevators\WebsocketServer;

require dirname(__DIR__) . '/bootstrap.php';

$loop = React\EventLoop\Factory::create();

$processor = new WebsocketServer($loop);

$app = new Ratchet\App('localhost', 8888, '0.0.0.0', $loop);
$app->route('/ws/', $processor, ['*']);
$app->run();
