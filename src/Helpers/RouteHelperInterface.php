<?php

namespace Rockberpro\RestRouter\Helpers;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
interface RouteHelperInterface
{
    public static function routeArgs($route_match): array|bool;
    public static function routeMatchArgs($route): array|bool;
    public static function routeVars($route): array|bool;
    public static function isAlphaNumeric($string): bool|int;
}