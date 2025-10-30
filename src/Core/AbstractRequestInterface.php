<?php

namespace Rockberpro\RestRouter\Core;

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