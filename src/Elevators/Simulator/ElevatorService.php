<?php


namespace Elevators\Simulator;


use SplObjectStorage;

class ElevatorService
{
    /** @var SplObjectStorage */
    protected $elevators;

    public function __construct()
    {
        $this->elevators = new SplObjectStorage;
    }

    public function addElevator(Elevator $elevator)
    {
        $this->elevators->attach($elevator);
    }

    /**
     * @return SplObjectStorage
     */
    public function getElevators()
    {
        return $this->elevators;
    }

    public function getCount() : int
    {
        return $this->elevators->count();
    }

    public function getClosest(int $floor, ElevatorStatus $elevatorStatus = null) : Elevator
    {
        $status = ($elevatorStatus !== null) ? $elevatorStatus->getValue() : null;

        /** @var Elevator|null $closest */
        $closest = null;
        /** @var Elevator $elevator */
        foreach ($this->elevators as $elevator) {
            if ($status !== null) {
                if ($elevator->getStatus() !== $status) {
                    continue;
                }
            }

            if ($closest === null || abs($floor - $closest->getFloor()) > abs($elevator->getFloor() - $floor)) {
                $closest = $elevator;
                continue;
            }
        }

        return $closest;
    }

}
