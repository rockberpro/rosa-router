<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Helpers\RequestAction;
use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\RequestData;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
interface AbstractRequestInterface
{
    public function buildUriRequest($routes, RequestData $requestData): Request;
    public function buildBodyRequest($routes, RequestData $requestData): Request;
    public function handle($routes, $method, $uri): RequestAction;
    public function map($routes, $method, $uri): array;
    public function match($mapped_routes, $uri): array;
}