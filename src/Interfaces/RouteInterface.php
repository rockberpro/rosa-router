<?php

namespace Rockberpro\RestRouter\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface RouteInterface
{
    public static function get($route, $method);
    public static function post($route, $method);
    public static function put($route, $method);
    public static function patch($route, $method);
    public static function delete($route, $method);
    public static function prefix($prefix);
    public function group($closure);
    public static function getRoutes();
}