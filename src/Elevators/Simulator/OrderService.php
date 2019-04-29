<?php


namespace Elevators\Simulator;


use SplObjectStorage;

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
    public function getCount() : int
    {
        return $this->orders->count();
    }
}
