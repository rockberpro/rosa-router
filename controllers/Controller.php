<?php

namespace Rockberpro\RestRouter\Controllers;

use Rockberpro\RestRouter\Controllers\Interfaces\ControllerInterface;
use Rockberpro\RestRouter\Response;

class Controller implements ControllerInterface
{
    /**
     * @param mixed $data
     * @param int $status
     * @return Response
     */
    public function response($data, $status): Response
    {
        return new Response($data, $status);
    }
}