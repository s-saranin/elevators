<?php


namespace Elevators\Simulator;


use Elevators\Database\Statistics;

/**
 * Class StatisticsService
 * @package Elevators\Simulator
 */
class StatisticsService
{
    /**
     * Информация, какой из лифтов прибыл на заказ, и как он двигался
     *
     * @param Statistics $databaseStatistics
     * @return string
     */
    public function getOrderList(Statistics $databaseStatistics)
    {
        $result = $databaseStatistics->getAllOrders();
        $output = '';
        foreach ($result as $stat) {
            $output .= "Order {$stat['order_id']} \t Elevator {$stat['elevator_id']} \t {$stat['from_floor']} -> {$stat['to_floor']}" . PHP_EOL;
        }

        return $output;
    }

    /**
     * Просмотр логов всех (за всё время) вызовов (в формате: какой лифт на какой этаж сколько раз приехал)
     *
     * @param Statistics $databaseStatistics
     * @return string
     */
    public function getOrderStatistics(Statistics $databaseStatistics)
    {
        $result = $databaseStatistics->getOrderStatistics();
        $output = '';
        foreach ($result as $stat) {
            $output .= "Elevator {$stat['elevator_id']} \t Floor {$stat['to_floor']} \t Count {$stat['count']}" . PHP_EOL;
        }

        return $output;
    }

    /**
     * Просмотр логов (за все время) движения лифтов за итерацию
     * (итерация - движения лифта до смены направления, например с 10->7->6->2)
     *
     * @param Statistics $databaseStatistics
     * @return string
     */
    public function getElevatorIntervals(Statistics $databaseStatistics)
    {
        $result = $databaseStatistics->getElevatorIntervals();

        $output = '';
        foreach ($result as $stat) {
            $output .= "===== Elevetor {$stat['elevator_id']} ====== " . PHP_EOL;

            $intervals = $this->divideInterval(json_decode($stat['interval']));
            $output .=  implode(', ', $intervals) . PHP_EOL . PHP_EOL;
        }

        return $output;
    }

    /**
     * @param array $interval
     * @return array
     */
    protected function divideInterval(array $interval)
    {
        $intervals = [];

        $previous = null;
        $sort = null;
        $currentInterval = [];
        foreach ($interval as $value) {
            if ($previous === null) {
                $currentInterval[] = $value;
                $previous = $value;
                continue;
            }

            if ($sort === null) {
                $sort = ($value > $previous)
                    ? 1
                    : -1;
            }

            if (
                ($sort === 1 && $value >= $previous)
                || ($sort === -1 && $value <= $previous)
            ) {
                $currentInterval[] = $value;
                $previous = $value;
            } else {
                $sort = -1 * abs($sort);
                $intervals[] = $currentInterval;
                $currentInterval = [$previous, $value];
                $previous = $value;
            }
        }

        if (! empty($currentInterval)) {
            $intervals[] = $currentInterval;
        }


        $intervals = array_map(function($interval) {
            return implode('->', $interval);
        }, $intervals);

        print_r($intervals);
        return $intervals;
    }
}
