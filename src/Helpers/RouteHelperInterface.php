<?php

namespace Rockberpro\RosaRouter\Helpers;

interface RouteHelperInterface
{
    public static function routeArgs($route_match);
    public static function routeMatchArgs($route);
    public static function routeVars($route);
    public static function isAlphaNumeric($string);
}