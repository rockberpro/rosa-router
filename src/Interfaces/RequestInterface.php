<?php

namespace Rockberpro\RestRouter\Interfaces;

use Rockberpro\RestRouter\Helpers\RequestAction;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface RequestInterface
{
    public static function body();
    public function handle($method, $uri, $query = null, $body = null);
    public function setAction(RequestAction $action);
    public function getAction();
    public function get($key);
}