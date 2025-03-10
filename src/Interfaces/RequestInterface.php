<?php

namespace Rockberpro\RestRouter\Interfaces;

use Rockberpro\RestRouter\Helpers\RequestAction;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Interfaces
 */
interface RequestInterface
{
    public static function body(): array|bool|string|null;
    public function handle($method, $uri, $query = null, $body = null): void;
    public function setAction(RequestAction $action): void;
    public function getAction(): RequestAction;
    public function get($key);
}