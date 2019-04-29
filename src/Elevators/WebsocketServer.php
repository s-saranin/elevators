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

    protected $connection = [
        'host'      => 'rabbitmq',
        'user'      => 'root',
        'password'  => 'root'
    ];
    protected $clients;
    protected $clientsIdList;


    protected $channel;

    /**
     * Processor constructor.
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->clients = new SplObjectStorage;
        $this->channel = (new Client($loop, $this->connection))->connect()->then(function (Client $client) {
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

    protected function prepareMessage($message)
    {
        /*$message = pg_escape_string($message);

        if (strlen($message) >= 255) {
            $message = substr($message, 0, 255);
        }

        $connection = "host=postgres dbname=elevators user=root password=root";
        $db = pg_connect($connection);
        $result = pg_query($db, "INSERT INTO messages (message) VALUES ('{$message}') RETURNING id");
        $id = pg_fetch_row($result)[0];

        return "Inserted message ({$id}): {$message}";*/
    }

    public function onOpen(ConnectionInterface $connection)
    {
        // Store the new connection to send messages to later
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
        // The connection is closed, remove it, as we can no longer send it messages
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
        echo " [x] Received " . $message . "\n";

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
