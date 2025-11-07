<?php

namespace Rockberpro\RosaRouter\Helpers;

class RouteHelper implements RouteHelperInterface
{
    /**
     * Get the route arguments
     * 
     * @method routeArgs
     * @param array $route_match
     * @return array
     */
    public static function routeArgs($route_match)
    {
        return preg_split('/(\/[\w]+\/)({[\w]+})/', $route_match[array_key_first($route_match)], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Get the route matching arguments
     * 
     * @method routeMatchArgs
     * @param string $route
     * @return array
     */
    public static function routeMatchArgs($route)
    {
        return preg_split('/({[\w]+})/', $route, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Get the route vars
     * 
     * @method routeVars
     * @param string $route
     * @return array
     */
    public static function routeVars($route)
    {
        return preg_split('/({[\w]+})/', $route, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Check if route is alphanumeric
     * 
     * @method isAlphaNumeric
     * @param string $string
     * @return bool
     */
    public static function isAlphaNumeric($string)
    {
        return preg_match('/^[a-zA-Z0-9_]*$/', $string);
    }
}