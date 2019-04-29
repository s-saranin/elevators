<?php


namespace Elevators\Database;


class AbstractConnection
{
    protected $connection;

    public function __construct(DatabaseConnectionInterface $connection)
    {
        $this->connection = $connection->getConnection();
    }
}