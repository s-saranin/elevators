<?php

namespace Elevators\Simulator;

use Elevators\Container;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Loader
 * @package Elevators\Simulator
 */
class Loader
{
    protected $configuration = [];

    /**
     * Loader constructor.
     */
    public function __construct()
    {
        $this->configuration = Yaml::parseFile(APP_ROOT . '/config/simulator.yaml');
    }

    /**
     * @return array|mixed
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return Simulator
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Exception
     */
    public function getSimulator() : Simulator
    {
        $floorCount = (int)$this->configuration['building']['floors'];
        $elevatorCount = (int)$this->configuration['building']['elevators'];

        $databaseElevator = Container::getContainer()->get('database.elevator');
        $databaseConfiguration = Container::getContainer()->get('database.configuration');
        $databaseOrder = Container::getContainer()->get('database.order');

        $buildingFloorsCount = new BuildingFloorsCount($floorCount);
        $elevatorService = new ElevatorService();
        $orderService = new OrderService();

        $applicationConfiguration = $databaseConfiguration->getConfiguration();

        if (! isset($applicationConfiguration['initialized'])) {
            /**
             * Инициализируем первоначальное состояние приложения
             */
            $databaseConfiguration->setupConfiguration([
                'initialized' => 'y',
                'floors' => $buildingFloorsCount->getCount(),
                'elevators' => $elevatorCount
            ]);


            for ($i = 1; $i <= $elevatorCount; $i++) {
                $floor = rand(1, $buildingFloorsCount->getCount());
                $elevatorService->make(
                    $databaseElevator,
                    $floor,
                    new ElevatorMoveDirection(ElevatorMoveDirection::MOVE_UP),
                    new ElevatorStatus(ElevatorStatus::ELEVATOR_IDLE)
                );
            }
        } else {
            /**
             * Загружаем состояние приложения из БД
             */
            foreach ((array)$databaseElevator->getAll() as $elevator) {
                $elevator = new Elevator(
                    $elevator['id'],
                    $elevator['floor'],
                    new ElevatorMoveDirection(ElevatorMoveDirection::MOVE_UP),
                    new ElevatorStatus(ElevatorStatus::ELEVATOR_IDLE)
                );
                $elevatorService->addElevator($elevator);
            }

            foreach ($databaseOrder->getNotFinishedList() as $order) {

                $orderElevator = (! empty($order['elevator_id']))
                    ? $elevatorService->getById((int)$order['elevator_id'])
                    : null;

                $order = new Order(
                    $order['id'],
                    $order['floor'],
                    new OrderStatus((int)$order['status']),
                    $orderElevator
                );
                $orderService->addOrder($order);
            }
        }

        $simulator = new Simulator($buildingFloorsCount, $elevatorService, $orderService);

        return $simulator;
    }

    /**
     * @return int
     */
    public function getThinkInterval() : int
    {
        return $this->configuration['think_interval'];
    }
}