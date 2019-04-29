<?php


namespace Elevators\Simulator\Render;


use Elevators\Simulator\Message\RenderMessage;
use Elevators\Simulator\Simulator;

interface RenderInterface
{
    public function render(Simulator $simulator) : RenderMessage;
}