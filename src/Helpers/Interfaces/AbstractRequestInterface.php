<?php

namespace Rockberpro\RestRouter\Helpers\Interfaces;

use Rockberpro\RestRouter\Request;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface AbstractRequestInterface
{
    public function buildUriRequest($routes, $method, $uri) : Request;
    public function buildFormRequest($routes, $method, $uri, $form) : Request;
    public function handle($routes, $method, $uri);
    public function map($routes, $method, $uri);
    public function match($mapped_routes, $uri);
}