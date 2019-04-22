<?php


namespace Elevators;

use Bunny\Channel;
use Bunny\Message;
use Exception;
use Bunny\Async\Client;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use function React\Promise\all as PromiseAll;
use React\Promise\PromiseInterface;
use function React\Promise\resolve as PromiseResolve;
use SplObjectStorage;

class Processor implements MessageComponentInterface
{
    protected $counter = 0;

    protected $connection = [
        'host'      => 'rabbitmq',
        'user'      => 'root',
        'password'  => 'root'
    ];
    protected $clients;
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
            return $channel->queueDeclare('rpc_queue')->then(function () use ($channel) {
                return $channel;
            });
        })->then(function (Channel $channel) {
            echo " [x] Awaiting RPC requests\n";
            $channel->consume(
                function (Message $message, Channel $channel, Client $client) {
                    $n = $message->content;
                    echo " [.] fib(", $n, ")\n";
                    $channel->publish(
                        (string)$this->prepareMessage($n),
                        [
                            'correlation_id' => $message->getHeader('correlation_id'),
                        ],
                        '',
                        $message->getHeader('reply_to')
                    )->then(function () use ($channel, $message) {
                        $channel->ack($message);
                    });
                },
                'rpc_queue'
            );
        });

    }

    protected function prepareMessage($message)
    {
        $message = pg_escape_string($message);

        if (strlen($message) >= 255) {
            $message = substr($message, 0, 255);
        }

        $connection = "host=postgres dbname=elevators user=root password=root";
        $db = pg_connect($connection);
        $result = pg_query($db, "INSERT INTO messages (message) VALUES ('{$message}') RETURNING id");
        $id = pg_fetch_row($result)[0];

        return "Inserted message ({$id}): {$message}";
    }

    public function increaseCounter()
    {
        $this->counter++;
    }

    public function getCounter()
    {
        return $this->counter;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function pushMessage($msg)
    {
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param Exception $e
     * @throws Exception
     */
    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $this->channel->then(function (Channel $channel) {
            return $channel->close();
        });
        $conn->close();
    }

    /**
     * Triggered when a client sends data through the socket
     *
     * @param ConnectionInterface $from The socket/connection that sent the message to your application
     * @param string $message The message received
     * @throws Exception
     */
    function onMessage(ConnectionInterface $from, $message)
    {
        echo " [x] Received " . $message . "\n";

        $this->channel->then(function (Channel $channel) {
            return PromiseAll([
                $channel->queueDeclare('', false, false, true),
                PromiseResolve($channel),
            ]);
        })->then(function ($values) use ($message) {
            list ($responseQueue, $channel) = $values;
            $corr_id = uniqid();
            $deferred = new Deferred();
            $channel->consume(
                function (Message $message, Channel $channel, Client $client) use ($deferred, $corr_id) {
                    if ($message->getHeader('correlation_id') != $corr_id) {
                        return;
                    }
                    $deferred->resolve($message->content);
                },
                $responseQueue->queue
            );
            $channel->publish(
                $message,
                [
                    'correlation_id' => $corr_id,
                    'reply_to' => $responseQueue->queue,
                ],
                '',
                'rpc_queue'
            );
            return $deferred->promise();
        })->then(function ($n) {
            echo " [.] Got ", $n, "\n";
        });
    }
}
