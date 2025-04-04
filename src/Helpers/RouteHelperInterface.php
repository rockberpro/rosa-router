<?php

namespace Rockberpro\RestRouter\Helpers;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
interface RouteHelperInterface
{
    public static function routeArgs($route_match);
    public static function routeMatchArgs($route);
    public static function routeVars($route);
    public static function isAlphaNumeric($string);
}