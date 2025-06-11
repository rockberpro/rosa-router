<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\RequestAction;
use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\RequestData;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Core
 */
interface AbstractRequestInterface
{
    public function buildUriRequest($routes, RequestData $requestData): Request;
    public function buildBodyRequest($routes, RequestData $requestData): Request;
    public function handle($routes, $method, $uri): RequestAction;
    public function map($routes, $method, $uri): array;
    public function match($mapped_routes, $uri): array;
}