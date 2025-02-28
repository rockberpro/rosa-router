<?php

namespace Rockberpro\RestRouter\Controllers;

use Rockberpro\RestRouter\Controllers\Interfaces\ControllerInterface;
use Rockberpro\RestRouter\Response;

class Controller implements ControllerInterface
{
    /**
     * @param mixed $data
     * @param mixed $code
     * @return void
     */
    public function response($data, $code)
    {
        Response::json($data, $code);
    }   
}