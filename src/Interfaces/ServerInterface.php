<?php

namespace Rockberpro\RestRouter\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface ServerInterface
{
    public static function uri();
    public static function query();
    public static function method();
    public static function key();
    public static function authorization();
    public static function routeArgv();
    public static function documentRoot();
    public static function serverName();
    public static function serverAddress();
    public static function userAgent();
    public static function remoteAddress();
    public static function targetAddress();
    public static function requestMethod();
    public static function requestUri();
}