<?php

namespace Rockberpro\RestRouter\Interfaces;

use Rockberpro\RestRouter\Helpers\RequestAction;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface ResponseInterface
{
    public static function json($data, $code);
}