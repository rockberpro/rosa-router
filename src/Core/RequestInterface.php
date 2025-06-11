<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\RequestAction;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface RequestInterface
{
    public static function body();
    public function handle(RequestData $requestData);
    public function setAction(RequestAction $action): void;
    public function getAction(): RequestAction;
    public function get($key);
}