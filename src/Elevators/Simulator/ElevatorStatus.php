<?php


namespace Elevators\Simulator;


use MyCLabs\Enum\Enum;

class ElevatorStatus extends Enum
{
    const ELEVATOR_IDLE = 0;
    const ELEVATOR_MOVE = 1;
    const ELEVATOR_LOAD = 2;
}