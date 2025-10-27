<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\RequestAction;
use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\RequestData;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Core
 */
interface AbstractRequestInterface
{
    public function buildRequest(RequestData $requestData): Request;
    public function handle($routes, $method, $uri): RequestAction;
    public function map($routes, $method, $uri): array;
    public function match($routes, $mapped_routes, $uri): array;
}