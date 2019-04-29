<?php


namespace Elevators;

use Bunny\Channel;
use Bunny\Message;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Exception;
use Bunny\Async\Client;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use SplObjectStorage;

class WebsocketServer implements MessageComponentInterface
{
    protected $counter = 0;

    protected $clients;
    protected $clientsIdList;

    protected $channel;

    /**
     * Processor constructor.
     * @param LoopInterface $loop
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(LoopInterface $loop)
    {
        $connection = Container::getContainer()->get('connection.rabbitmq');

        $this->clients = new SplObjectStorage;
        $this->channel = (new Client($loop, $connection))->connect()->then(function (Client $client) {
            return $client->channel();
        });

        $this->channel->then(function (Channel $channel) {
            return $channel->exchangeDeclare('exchange', 'topic')->then(function () use ($channel) {
                return $channel->queueDeclare('', false, false, true, false);
            })->then(function (MethodQueueDeclareOkFrame $frame) use ($channel) {
                return $channel->queueBind($frame->queue, 'exchange', 'elevators.response')->then(function () use ($frame) {
                    return $frame;
                });
            })->then(function (MethodQueueDeclareOkFrame $frame) use ($channel) {
                $channel->consume(
                    function (Message $message) {
                        $client = $message->getHeader('client_id');
                        $this->pushMessage($message->content, $client);
                    },
                    $frame->queue,
                    '',
                    false,
                    true
                );
            });
        });
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $this->clients->attach($connection);
        $this->clientsIdList[$connection->resourceId] = $connection;

        echo "New connection! ({$connection->resourceId})\n";
    }

    public function pushMessage($msg, $clientId = null)
    {
        if (! empty($clientId) && isset($this->clientsIdList[$clientId])) {
            $this->clientsIdList[$clientId]->send($msg);
        } else {
            foreach ($this->clients as $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->clients->detach($connection);
        unset($this->clientsIdList[$connection->resourceId]);

        echo "Connection {$connection->resourceId} has disconnected\n";
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $connection
     * @param Exception $e
     * @throws Exception
     */
    public function onError(ConnectionInterface $connection, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $this->channel->then(function (Channel $channel) {
            return $channel->close();
        });
        $connection->close();
    }

    /**
     * Triggered when a client sends data through the socket
     *
     * @param ConnectionInterface $from The socket/connection that sent the message to your application
     * @param string $message The message received
     * @throws Exception
     */
    function onMessage(ConnectionInterface $connection, $message)
    {
        $this->channel->then(function (Channel $channel) {
            return $channel->exchangeDeclare('exchange', 'topic')->then(function () use ($channel) {
                return $channel;
            });
        })->then(function (Channel $channel) use ($connection, $message) {
            $channel->publish(
                $message,
                [
                    'client_id' => $connection->resourceId
                ],
                'exchange',
                'elevators.request'
            );
        });
    }
}
