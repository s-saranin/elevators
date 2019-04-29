<?php


namespace Elevators\Simulator;


use SplObjectStorage;

/**
 * Class OrderService
 * @package Elevators\Simulator
 */
class OrderService
{
    /** @var SplObjectStorage */
    protected $orders;

    /**
     * OrderService constructor.
     */
    public function __construct()
    {
        $this->orders = new SplObjectStorage;
    }

    /**
     * @param Order $order
     */
    public function addOrder(Order $order)
    {
        $this->orders->attach($order);
    }

    /**
     * @return SplObjectStorage
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->orders->count();
    }

    /**
     * @param \Elevators\Database\Order $databaseOrder
     * @param int $floor
     * @return int
     * @throws \Exception
     */
    public function make(\Elevators\Database\Order $databaseOrder, int $floor) : int
    {
        $id = $databaseOrder->add($floor);
        if (empty($id)) {
            throw new \Exception('Order creating error.');
        }

        $order = new Order($id, $floor);

        $this->addOrder($order);

        return $id;
    }
}
