<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\Route;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface RouteInterface
{
    public static function get($route, $method): void;
    public static function post($route, $method): void;
    public static function put($route, $method): void;
    public static function patch($route, $method): void;
    public static function delete($route, $method): void;
    public static function prefix($prefix): Route;
    public function group($closure): void;
    public static function getRoutes(): array;
}