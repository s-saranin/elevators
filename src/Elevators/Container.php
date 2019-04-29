<?php


namespace Elevators;


class Container
{
    public static $container;

    public static function setContiner(\DI\Container $container)
    {
        self::$container = $container;
    }

    public static function getContainer() : \DI\Container
    {
        return self::$container;
    }
}