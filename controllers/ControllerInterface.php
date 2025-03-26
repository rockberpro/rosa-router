<?php

namespace Rockberpro\RestRouter\Controllers;

/**
 * @author Samuel Oberger Rockenbach
 */
interface ControllerInterface
{
    public function response($data, $status);
}