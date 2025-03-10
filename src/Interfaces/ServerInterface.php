<?php

namespace Rockberpro\RestRouter\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Interfaces
 */
interface ServerInterface
{
    public static function uri(): string;
    public static function query(): string;
    public static function method(): string;
    public static function key(): string;
    public static function authorization(): string;
    public static function routeArgv(): string;
    public static function documentRoot(): string;
    public static function serverName(): string;
    public static function serverAddress(): string;
    public static function userAgent(): string;
    public static function remoteAddress(): string;
    public static function targetAddress(): string;
    public static function requestMethod(): string;
    public static function requestUri(): string;
}