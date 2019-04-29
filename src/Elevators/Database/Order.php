<?php


namespace Elevators\Database;


use Elevators\Simulator\OrderStatus;

class Order extends AbstractConnection
{
    /**
     * Список незавершенных заказов
     *
     * @return array
     */
    public function getNotFinishedList() : array
    {
        $finishedStatus = OrderStatus::ORDER_FINISHED;
        $result = pg_query(
            $this->connection,
            "SELECT id, floor, status, elevator_id FROM orders WHERE status < {$finishedStatus} ORDER BY id"
        );

        $result = pg_fetch_all($result, PGSQL_ASSOC);

        if (empty($result)) {
            $result = [];
        }
        return $result;
    }

    /**
     * @param int $floor
     * @return int
     */
    public function add(int $floor) : int
    {
        $id = 0;
        $initStatus = OrderStatus::ORDER_PENDING;

        if ($floor > 0) {
            $result = pg_query(
                $this->connection,
                "INSERT INTO orders (floor, status, elevator_id) VALUES ({$floor}, {$initStatus}, null) RETURNING id"
            );
            $id = pg_fetch_row($result)[0];
        }

        return $id;
    }

    /**
     * @param int $id
     * @param array $fields
     */
    public function update(int $id, array $fields)
    {
        if ($id > 0) {
            pg_update($this->connection, 'orders', $fields, ['id' => $id]);
        }
    }
}
