<?php

namespace Rockberpro\RestRouter\Controllers;

interface ControllerInterface
{
    public function response($data, $status);
}