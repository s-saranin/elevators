<?php

namespace Elevators\Simulator;

use Symfony\Component\Yaml\Yaml;

class Loader
{
    protected $configuration = [];

    public function __construct()
    {
        $this->configuration = Yaml::parseFile(APP_ROOT . '/config/simulator.yaml');
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getSimulator() : Simulator
    {
        $floorCount = (int)$this->configuration['building']['floors'];
        $elevatorCount = (int)$this->configuration['building']['elevators'];

        $buildingFloorsCount = new BuildingFloorsCount($floorCount);

        $elevatorService = new ElevatorService();
        for ($i = 1; $i <= $elevatorCount; $i++) {
            $floor = rand(1, $buildingFloorsCount->getCount());
            $elevator = new Elevator(
                $i,
                $floor,
                new ElevatorMoveDirection(ElevatorMoveDirection::MOVE_UP)
            );
            $elevatorService->addElevator($elevator);
        }

        $orderService = new OrderService();

        $simulator = new Simulator($buildingFloorsCount, $elevatorService, $orderService);

        return $simulator;
    }

    public function getThinkInterval() : int
    {
        return $this->configuration['think_interval'];
    }
}