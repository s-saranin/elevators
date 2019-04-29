<?php


namespace Elevators;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Elevators\Simulator\Loader;
use Elevators\Simulator\Render\RenderInterface;
use Elevators\Simulator\Order;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;


class SimulatorServer
{
    protected $thinkInterval;
    protected $simulator;

    protected $connection = [
        'host'      => 'rabbitmq',
        'user'      => 'root',
        'password'  => 'root'
    ];

    /** @var Channel|PromiseInterface */
    protected $channel;

    /**
     * Processor constructor.
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $loader = new Loader();
        $this->simulator = $loader->getSimulator();
        $this->thinkInterval = $loader->getThinkInterval();

        $this->initChannelQueue($loop);
        $this->initRequestHandler();
    }

    public function think(LoopInterface $loop, RenderInterface $render)
    {
        $this->simulator->think($loop);

        $message = $this->simulator->render($render);
        $this->publish($message->asJson());
    }

    public function publish(string $message, array $headers = [])
    {
        $this->channel->then(function (Channel $channel) use ($message, $headers) {
            return $channel->publish($message, $headers, 'exchange', 'elevators.response');
        });
    }

    public function getThinkInterval()
    {
        return $this->thinkInterval;
    }

    protected function initChannelQueue(LoopInterface $loop)
    {
        $this->channel = (new Client($loop, $this->connection))->connect()->then(function (Client $client) {
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

    protected function handleRequest($message, $client)
    {
        $request = json_decode($message, true);
        print_r($request);
        if (isset($request['type'])) {
            switch ($request['type']) {
                case 'order':
                    $order = new Order(rand(0, 1000), $request['value']);
                    $this->simulator->getOrderService()->addOrder($order);
                    $this->publish(
                        json_encode([
                            'type' => 'order_created',
                            'value' => $order->getId()
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
