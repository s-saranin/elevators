<?php


namespace Elevators\Database;


/**
 * Class Elevator
 * @package Elevators\Database
 */
class Elevator extends AbstractConnection
{
    /**
     * Добавление записи лифта
     *
     * @param int $floor
     * @return int
     */
    public function add(int $floor)
    {
        $id = 0;

        if ($floor > 0) {
            $result = pg_query(
                $this->connection,
                "INSERT INTO elevators (floor) VALUES ({$floor}) RETURNING id"
            );
            $id = pg_fetch_row($result)[0];
        }

        return $id;
    }

    /**
     * @return array
     */
    public function getAll() : array
    {
        $result = pg_query(
            $this->connection,
            "SELECT id, floor FROM elevators ORDER BY id"
        );

        return pg_fetch_all($result, PGSQL_ASSOC);

    }

    /**
     * @param int $id
     * @param int $floor
     */
    public function update(int $id, int $floor)
    {
        if ($id > 0) {
            pg_update($this->connection, 'elevators', ['floor' => $floor], ['id' => $id]);
        }
    }
}