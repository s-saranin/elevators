<?php


namespace Elevators\Simulator;


class Elevator
{
    protected $id;
    protected $floor;
    protected $status;
    protected $direction;

    public function __construct(int $id, int $floor, ElevatorMoveDirection $direction, ElevatorStatus $status = null)
    {
        $this->id = $id;
        $this->floor = $floor;

        $this->setDirection($direction);

        if ($status === null) {
            $status = new ElevatorStatus(ElevatorStatus::ELEVATOR_IDLE);
        }
        $this->setStatus($status);
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getDirection()
    {
        return $this->direction;
    }

    public function setDirection(ElevatorMoveDirection $direction)
    {
        $this->direction = $direction->getValue();
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(ElevatorStatus $status)
    {
        $this->status = $status->getValue();
    }

    public function getFloor()
    {
        return $this->floor;
    }

    public function setFloor(int $floor)
    {
        $this->floor = $floor;
    }

    public function __toString()
    {
        $status = ElevatorStatus::search($this->getStatus());
        $direction = ElevatorMoveDirection::search($this->getDirection());
        return "Elevator {$this->id} | Floor {$this->getFloor()} | Status {$status} | Direction {$direction}";
    }
}
