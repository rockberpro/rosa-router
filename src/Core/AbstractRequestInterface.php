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
    public function buildRequest(RequestData $data): Request;
    public function handle($routes, $uri): RequestAction;
    public function map($routes, $uri): array;
    public function match($routes, $mapped_routes, $uri): array;
}