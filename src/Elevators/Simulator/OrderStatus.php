<?php


namespace Elevators\Simulator;


use MyCLabs\Enum\Enum;

/**
 * Class OrderStatus
 * @package Elevators\Simulator
 */
class OrderStatus extends Enum
{
    /** @var int Ожидание назначение лифта */
    const ORDER_PENDING = 0;
    /** @var int Выполнение заказа */
    const ORDER_PROCESSING = 1;
    /** @var int Лифт прибыл, завершение заказа */
    const ORDER_FINISHED = 2;
}
