<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Helpers\RequestAction;
use Rockberpro\RestRouter\Request;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
interface AbstractRequestInterface
{
    public function buildUriRequest($routes, $method, $uri): Request;
    public function buildBodyRequest($routes, $method, $uri, $body): Request;
    public function handle($routes, $method, $uri): RequestAction;
    public function map($routes, $method, $uri): array;
    public function match($mapped_routes, $uri): array;
}