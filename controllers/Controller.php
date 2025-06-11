<?php

namespace Rockberpro\RestRouter\Controllers;

use Rockberpro\RestRouter\Controllers\ControllerInterface;
use Rockberpro\RestRouter\Core\Response;

class Controller implements ControllerInterface
{
    /**
     * @param array|object $data
     * @param int $status
     * @return Response
     */
    public function response($data, $status): Response
    {
        return new Response($data, $status);
    }
}