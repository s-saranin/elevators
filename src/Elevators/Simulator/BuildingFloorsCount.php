<?php


namespace Elevators\Simulator;


use InvalidArgumentException;

class BuildingFloorsCount
{
    const __default = 10;

    /**
     * @var int
     */
    protected $count;

    public function __construct(int $value = self::__default)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException(
                'Building floors count must be greater than zero, ' . $value . ' passed.'
            );
        }

        $this->count = $value;
    }

    public function getCount() : int
    {
        return $this->count;
    }

    public function __toString() : string
    {
        return (string)$this->getCount();
    }
}
