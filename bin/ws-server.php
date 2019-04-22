<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$processor = new \Elevators\Processor($loop);




$loop->addPeriodicTimer(1, function() use ($processor) {
    $processor->increaseCounter();
    $message = (new \DateTime())->format(\DateTime::ISO8601);
    $memory = round(memory_get_usage() /1048576,2);
    $processor->pushMessage($processor->getCounter() . ' - ' . $message . " ({$memory} Mb)");
});


$app = new Ratchet\App('localhost', 8888, '0.0.0.0', $loop);
$app->route('/ws/', $processor);
$app->run();