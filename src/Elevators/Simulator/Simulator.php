<?php

namespace Elevators\Simulator;


use Elevators\Simulator\Render\RenderInterface;
use React\EventLoop\LoopInterface;

class Simulator
{
    /**
     * @var BuildingFloorsCount
     */
    protected $floorCount;

    /**
     * @var ElevatorService
     */
    protected $elevatorService;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * Simulator constructor.
     * @param BuildingFloorsCount $floorCount
     * @param ElevatorService $elevatorService
     * @param OrderService $orderService
     */
    public function __construct(BuildingFloorsCount $floorCount, ElevatorService $elevatorService, OrderService $orderService)
    {
        $this->floorCount = $floorCount;
        $this->elevatorService = $elevatorService;
        $this->orderService = $orderService;
    }

    public function think(LoopInterface $loop)
    {
        $orders = $this->orderService->getOrders();

        /**
         * Если заказов нет - перемещаем один лифт на первый этаж
         */
        if ($orders->count() === 0) {
            $closestElevator = $this->elevatorService->getClosest(1);
            if (! empty($closestElevator) && $closestElevator->getFloor() !== 1) {
                $this->moveElevator($loop, $closestElevator, new ElevatorMoveDirection(ElevatorMoveDirection::MOVE_DOWN));
            }
        } else {
            /** @var Order $order */
            foreach ($orders as $order) {
                $status = $order->getStatus();

                switch ($status) {
                    case OrderStatus::ORDER_PENDING:
                        if ($order->getElevator() === null) {
                            $closestElevator = $this->elevatorService->getClosest(
                                $order->getFloor(),
                                new ElevatorStatus(ElevatorStatus::ELEVATOR_IDLE)
                            );
                            if (! empty($closestElevator)) {
                                $order->setElevator($closestElevator);
                                $order->setStatus(new OrderStatus(OrderStatus::ORDER_PROCESSING));
                            } else {
                                continue 2;
                            }
                        }
                        break;
                    case OrderStatus::ORDER_PROCESSING:
                        $orderElevator = $order->getElevator();
                        if ($orderElevator->getFloor() !== $order->getFloor()) {
                            $direction = ($order->getFloor() > $orderElevator->getFloor())
                                ? new ElevatorMoveDirection(ElevatorMoveDirection::MOVE_UP)
                                : new ElevatorMoveDirection(ElevatorMoveDirection::MOVE_DOWN);

                            $this->moveElevator($loop, $orderElevator, $direction);
                        } else {
                            $this->openElevator($loop, $orderElevator);
                            $order->setStatus(new OrderStatus(OrderStatus::ORDER_FINISHED));
                        }
                        break;
                    case OrderStatus::ORDER_FINISHED:
                        $orders->detach($order);
                        break;
                }
            }
        }
    }

    protected function moveElevator(LoopInterface $loop, Elevator $elevator, ElevatorMoveDirection $direction)
    {
        $maxFloor = $this->floorCount->getCount();
        $minFloor = 1;

        if ($elevator->getStatus() !== ElevatorStatus::ELEVATOR_MOVE) {

            $nextFloor = $currentFloor = $elevator->getFloor();

            switch ($direction->getValue()) {
                case ElevatorMoveDirection::MOVE_UP:
                    $nextFloor = $currentFloor + 1;
                    break;
                case ElevatorMoveDirection::MOVE_DOWN:
                    $nextFloor = $currentFloor - 1;
                    break;
            }

            if ( ($minFloor <= $nextFloor) && ($nextFloor <= $maxFloor)) {
                if ($elevator->getDirection() !== $direction->getValue()) {
                    $elevator->setDirection($direction);
                }

                $elevator->setFloor($nextFloor);

                $elevator->setStatus(new ElevatorStatus(ElevatorStatus::ELEVATOR_MOVE));
                $loop->addTimer(2, function () use ($elevator) {
                    if ($elevator->getStatus() === ElevatorStatus::ELEVATOR_MOVE) {
                        $elevator->setStatus(new ElevatorStatus(ElevatorStatus::ELEVATOR_IDLE));
                    }
                });
            }
        }
    }

    /**
     * @param LoopInterface $loop
     * @param Elevator $elevator
     */
    protected function openElevator(LoopInterface $loop, Elevator $elevator)
    {
        $elevator->setStatus(new ElevatorStatus(ElevatorStatus::ELEVATOR_LOAD));
        $loop->addTimer(5, function () use ($elevator) {
            if ($elevator->getStatus() === ElevatorStatus::ELEVATOR_LOAD) {
                $elevator->setStatus(new ElevatorStatus(ElevatorStatus::ELEVATOR_IDLE));
            }
        });
    }

    /**
     * @return BuildingFloorsCount
     */
    public function getFloorCount(): BuildingFloorsCount
    {
        return $this->floorCount;
    }

    /**
     * @return ElevatorService
     */
    public function getElevatorService(): ElevatorService
    {
        return $this->elevatorService;
    }

    /**
     * @return OrderService
     */
    public function getOrderService(): OrderService
    {
        return $this->orderService;
    }

    /**
     * @param RenderInterface $render
     * @return Message\RenderMessage
     */
    public function render(RenderInterface $render)
    {
        return $render->render($this);
    }
}
