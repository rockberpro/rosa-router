<?php

namespace Rosa\Router\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @version 1.0
 * @package Rosa\Router
 */
interface RouteInterface
{
    public static function get($route, $method);
    public static function post($route, $method);
    public static function put($route, $method);
    public static function patch($route, $method);
    public static function delete($route, $method);
    public static function group($prefix, $clojure);
    public function private();
    public function public();
    public static function getRoutes();
}