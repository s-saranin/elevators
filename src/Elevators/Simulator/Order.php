<?php


namespace Elevators\Simulator;


use Elevators\Container;

/**
 * Class Order
 * @package Simulator
 */
class Order
{
    protected $id;
    protected $floor;
    protected $elevator;
    protected $status;

    /**
     * Order constructor.
     * @param int $id
     * @param int $floor
     * @param OrderStatus|null $status
     * @param Elevator|null $elevator
     */
    public function __construct(int $id, int $floor, OrderStatus $status = null, Elevator $elevator = null)
    {
        $this->id = $id;

        $this->floor = $floor;

        if ($status === null) {
            $status = new OrderStatus(OrderStatus::ORDER_PENDING);
        }
        $this->setStatus($status);

        if ($elevator !== null) {
            $this->setElevator($elevator);
        }
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * @param OrderStatus $status
     */
    public function setStatus(OrderStatus $status)
    {
        $this->status = $status->getValue();
    }

    /**
     * @return int
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * @return Elevator|null
     */
    public function getElevator()
    {
        return $this->elevator;
    }

    /**
     * @param Elevator $elevator
     */
    public function setElevator(Elevator $elevator): void
    {
        $this->elevator = $elevator;
    }

    public function save(\Elevators\Database\Order $databaseOrder)
    {
        $databaseOrder->update($this->getId(), [
            'floor' => $this->getFloor(),
            'elevator_id' => $this->getElevator()->getId(),
            'status' => $this->getStatus()
        ]);
    }

    public function __toString()
    {
        $status = OrderStatus::search($this->getStatus());
        $elevator = $this->getElevator();
        $elevator = ($elevator instanceof Elevator) ? $elevator->getId() : 0;
        return "Order {$this->id} | Floor {$this->getFloor()} | Status {$status} | Elevator {$elevator}";
    }
}
