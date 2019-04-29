<?php


namespace Elevators\Simulator;


use SplObjectStorage;

/**
 * Class ElevatorService
 * @package Elevators\Simulator
 */
class ElevatorService
{
    /** @var SplObjectStorage */
    protected $elevators;

    public function __construct()
    {
        $this->elevators = new SplObjectStorage;
    }

    /**
     * @param Elevator $elevator
     */
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

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->elevators->count();
    }

    /**
     * @param int $id
     * @return Elevator|object|null
     */
    public function getById(int $id)
    {
        /** @var Elevator $elevator */
        foreach ($this->elevators as $elevator) {
            if ($elevator->getId() === $id) {
                return $elevator;
            }
        }
        return null;
    }

    /**
     * Вычисление ближайшего к переданному этажу лифта
     *
     * @param int $floor
     * @param ElevatorStatus|null $elevatorStatus
     * @return Elevator|null
     */
    public function getClosest(int $floor, ElevatorStatus $elevatorStatus = null)
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

    /**
     * Создание лифта с присвоением ID
     *
     * @param \Elevators\Database\Elevator $databaseElevator
     * @param int $floor
     * @param ElevatorStatus|null $elevatorStatus
     * @param ElevatorMoveDirection|null $direction
     * @throws \Exception
     */
    public function make(
        \Elevators\Database\Elevator $databaseElevator,
        int $floor,
        ElevatorMoveDirection $direction = null,
        ElevatorStatus $elevatorStatus = null
    ) {
        $id = $databaseElevator->add($floor);
        if (empty($id)) {
            throw new \Exception('Elevator creating error.');
        }

        $elevator = new Elevator($id, $floor, $direction, $elevatorStatus);

        $this->addElevator($elevator);
    }

}
