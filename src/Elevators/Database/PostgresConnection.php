<?php


namespace Elevators\Database;


class PostgresConnection implements DatabaseConnectionInterface
{
    protected $connection;

    /**
     * PostgresConnection constructor.
     * @param string $host
     * @param string $dbname
     * @param string $user
     * @param string $password
     */
    public function __construct(string $host, string $dbname, string $user, string $password)
    {
        $connection = "host={$host} dbname={$dbname} user={$user} password={$password}";
        $this->connection = pg_connect($connection);
    }

    /**
     * @return resource
     */
    public function getConnection()
    {
        return $this->connection;
    }
}