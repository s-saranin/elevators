<?php


namespace Elevators\Simulator\Render;

use DateTime;
use Elevators\Simulator\Elevator;
use Elevators\Simulator\ElevatorStatus;
use Elevators\Simulator\Message\RenderMessage;
use Elevators\Simulator\Order;
use Elevators\Simulator\Simulator;

class Ascii implements RenderInterface
{
    /** @var RenderMessage  */
    protected $message;

    public function __construct()
    {
        $this->message = new RenderMessage();
    }

    public function render(Simulator $simulator) : RenderMessage
    {
        $tiles = [];

        $floorCount = $simulator->getFloorCount()->getCount();
        $elevatorService = $simulator->getElevatorService();

        $elevatorCount = $elevatorService->getCount();

        $buildingHeight = $floorCount + 1;
        $buildingWidth = $elevatorCount + 12;

        for ($y = 0; $y <= $buildingHeight; $y++) {
            for ($x = 0; $x <= $buildingWidth; $x++) {
                if ($y == 0 && $x == 0) {
                    $tiles[$y][$x] = '╚═';
                } elseif ($y == $buildingHeight && $x == $buildingWidth) {
                    $tiles[$y][$x] = '╗';
                } elseif ($y == 0 && $x == $buildingWidth) {
                    $tiles[$y][$x] = '╝';
                } elseif ($y == $buildingHeight && $x == 0) {
                    $tiles[$y][$x] = '╔═';
                } elseif ($y == 0 || $y == $buildingHeight) {
                    $tiles[$y][$x] = '═';
                } elseif ($x == $buildingWidth) {
                    $tiles[$y][$x] = '║';
                } elseif ($x == 0) {
                    $tiles[$y][$x] = str_pad($y, 2);
                } else {
                    $tiles[$y][$x] = ' ';
                }
            }
        }

        $elevatorStatus = '';
        $elevators = $elevatorService->getElevators();

        $x = 6;
        /** @var Elevator $elevator */
        foreach ($elevators as $elevator) {
            $y = $elevator->getFloor();
            $tile = ($elevator->getStatus() === ElevatorStatus::ELEVATOR_LOAD)
                ? '▒'
                : '■';
            $tiles[$y][$x] = $tile;
            $x++;

            $elevatorStatus .= (string)$elevator . PHP_EOL;
        }

        $orderStatus = '';
        $orders = $simulator->getOrderService()->getOrders();

        $x = 2;
        /** @var Order $order */
        foreach ($orders as $order) {
            $y = $order->getFloor();
            $tiles[$y][$x] = 'O';

            $orderStatus .= (string)$order . PHP_EOL;
        }

        $render = (new DateTime())->format(DateTime::ISO8601) . ' ' . round(memory_get_usage() /1048576,2) . 'Mb' . PHP_EOL;

        for ($y = $buildingHeight; $y >= 0; $y--) {
            for ($x = 0; $x <= $buildingWidth; $x++) {
                $render .= $tiles[$y][$x];
            }
            $render .= PHP_EOL;
        }

        $render .= PHP_EOL . $elevatorStatus;
        $render .= PHP_EOL . $orderStatus;

        $this->message->setRenderText($render);

        return $this->message;
    }
}
