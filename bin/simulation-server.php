<?php

use Elevators\Simulator\Render\Ascii;
use Elevators\SimulatorServer;

require dirname(__DIR__) . '/bootstrap.php';

$loop = React\EventLoop\Factory::create();

$processor = new SimulatorServer($loop);

$loop->addPeriodicTimer($processor->getThinkInterval(), function() use ($processor, $loop) {
    $processor->think($loop, new Ascii());
});

$loop->run();
