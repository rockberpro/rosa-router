<?php

namespace Rockberpro\RestRouter\Controllers\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 */
interface ControllerInterface
{
    public function response($data, $code);
}