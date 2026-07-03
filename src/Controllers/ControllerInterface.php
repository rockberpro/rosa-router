<?php

namespace Rockberpro\RosaRouter\Controllers;

interface ControllerInterface
{
    public function response($data, $status);
}