<?php


namespace Elevators\Database;

/**
 * Class Statistics
 * @package Elevators\Database
 */
class Statistics extends AbstractConnection
{
    /**
     * @param $fields
     */
    public function add($fields)
    {
        pg_insert($this->connection, 'statistics', $fields);
    }

    /**
     * @return array
     */
    public function getAllOrders()
    {
        $result = pg_query($this->connection, '
            SELECT order_id, elevator_id, from_floor, to_floor, direction
            FROM statistics
            ORDER BY order_id, elevator_id;
        ');
        if (! empty($result)) {
            $result = pg_fetch_all($result, PGSQL_ASSOC);
        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getElevatorIntervals()
    {
        $result = pg_query($this->connection, '
            SELECT elevator_id, array_to_json(array_agg(to_floor)) AS interval
            FROM statistics
            GROUP BY elevator_id
            ORDER BY elevator_id;
        ');
        if (! empty($result)) {
            $result = pg_fetch_all($result, PGSQL_ASSOC);
        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getOrderStatistics()
    {
        $result = pg_query($this->connection, '
            SELECT elevator_id, to_floor, count(*) AS count
            FROM statistics
            GROUP BY elevator_id, to_floor
            ORDER BY elevator_id, to_floor;
        ');
        if (! empty($result)) {
            $result = pg_fetch_all($result, PGSQL_ASSOC);
        } else {
            $result = [];
        }

        return $result;
    }
}
