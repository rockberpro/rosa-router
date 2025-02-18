<?php

namespace Rockberpro\RestRouter\Utils;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Utils
 */
class UrlParser
{
    public static function pathQuery($uri)
    {
        $vars = [];
        parse_str(
            parse_url($uri, PHP_URL_QUERY) ?? '',
            $vars
        );

        return $vars['path'] ?? '';
    }

    public static function query($uri)
    {
        $vars = [];
        parse_str(
            parse_url($uri, PHP_URL_QUERY) ?? '',
            $vars
        );

        return $vars;
    }

    public static function path($uri)
    {
        $vars = [];
        parse_str(
            parse_url($uri, PHP_URL_PATH) ?? '',
            $vars
        );

        return array_key_first($vars);
    }
}