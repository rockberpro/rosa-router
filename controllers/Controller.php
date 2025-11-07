<?php

namespace Rockberpro\RosaRouter\Controllers;

use Rockberpro\RosaRouter\Core\Response;

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