<?php

namespace Rockberpro\RestRouter\Controllers;

use Rockberpro\RestRouter\Controllers\Interfaces\ControllerInterface;

class Controller implements ControllerInterface
{
    /**
     * @param mixed $data
     * @param int $status
     * @return array [data, status]
     */
    public function response($data, $status)
    {
        return ['data' => $data, 'status' => $status];
    }   
}