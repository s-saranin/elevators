<?php


namespace Elevators;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Elevators\Simulator\Loader;
use Elevators\Simulator\Render\RenderInterface;
use Elevators\Simulator\StatisticsService;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;


class SimulatorServer
{
    protected $thinkInterval;
    protected $simulator;

    /** @var Channel|PromiseInterface */
    protected $channel;

    /**
     * Processor constructor.
     * @param LoopInterface $loop
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(LoopInterface $loop)
    {
        $loader = new Loader();
        $this->simulator = $loader->getSimulator();
        $this->thinkInterval = $loader->getThinkInterval();

        $this->initChannelQueue($loop);
        $this->initRequestHandler();
    }

    /**
     * @param LoopInterface $loop
     * @param RenderInterface $render
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function think(LoopInterface $loop, RenderInterface $render)
    {
        $this->simulator->think($loop);

        $message = $this->simulator->render($render);
        $this->publish($message->asJson());
    }

    /**
     * @param string $message
     * @param array $headers
     */
    public function publish(string $message, array $headers = [])
    {
        $this->channel->then(function (Channel $channel) use ($message, $headers) {
            return $channel->publish($message, $headers, 'exchange', 'elevators.response');
        });
    }

    /**
     * @return int
     */
    public function getThinkInterval()
    {
        return $this->thinkInterval;
    }

    /**
     * @param LoopInterface $loop
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function initChannelQueue(LoopInterface $loop)
    {
        $connection = Container::getContainer()->get('connection.rabbitmq');
        $this->channel = (new Client($loop, $connection))->connect()->then(function (Client $client) {
            return $client->channel();
        })->then(function (Channel $channel) {
            return $channel->exchangeDeclare('exchange', 'topic')->then(function () use ($channel) {
                return $channel;
            });
        });
    }

    protected function initRequestHandler()
    {
        $this->channel->then(function (Channel $channel) {
            return $channel->exchangeDeclare('exchange', 'topic')->then(function () use ($channel) {
                return $channel->queueDeclare('', false, false, true, false);
            })->then(function (MethodQueueDeclareOkFrame $frame) use ($channel) {
                return $channel->queueBind($frame->queue, 'exchange', 'elevators.request')->then(function () use ($frame) {
                    return $frame;
                });
            })->then(function (MethodQueueDeclareOkFrame $frame) use ($channel) {
                $channel->consume(
                    function (Message $message) {
                        $client = $message->getHeader('client_id');
                        $this->handleRequest($message->content, $client);
                    },
                    $frame->queue,
                    '',
                    false,
                    true
                );
            });
        });
    }

    /**
     * @param $message
     * @param $client
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function handleRequest($message, $client)
    {
        $request = json_decode($message, true);

        if (isset($request['type'])) {
            switch ($request['type']) {
                case 'order':
                    $databaseOrder = Container::getContainer()->get('database.order');
                    $id = $this->simulator->getOrderService()->make($databaseOrder, (int)$request['value']);
                    $this->publish(
                        json_encode([
                            'type' => 'order_created',
                            'value' => $id
                        ]),
                        [
                            'client_id' => $client
                        ]
                    );
                    break;
                case 'order-list':
                    $databaseStatistics = Container::getContainer()->get('database.statistics');
                    $statisticService = new StatisticsService();

                    $output = $statisticService->getOrderList($databaseStatistics);
                    $this->publish(
                        json_encode([
                            'type' => 'information',
                            'value' => $output
                        ]),
                        [
                            'client_id' => $client
                        ]
                    );
                    break;
                case 'statistics':
                    $databaseStatistics = Container::getContainer()->get('database.statistics');
                    $statisticService = new StatisticsService();

                    $output = $statisticService->getOrderStatistics($databaseStatistics);
                    $this->publish(
                        json_encode([
                            'type' => 'information',
                            'value' => $output
                        ]),
                        [
                            'client_id' => $client
                        ]
                    );
                    break;
                case 'iterations':
                    $databaseStatistics = Container::getContainer()->get('database.statistics');
                    $statisticService = new StatisticsService();

                    $output = $statisticService->getElevatorIntervals($databaseStatistics);
                    $this->publish(
                        json_encode([
                            'type' => 'information',
                            'value' => $output
                        ]),
                        [
                            'client_id' => $client
                        ]
                    );
                    break;
            }
        }
    }
}
