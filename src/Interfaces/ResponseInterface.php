<?php

namespace Rockberpro\RestRouter\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Interfaces
 */
interface ResponseInterface
{
    public function __construct($code, $status);
    public function response();
    public static function json($data, $status): never;
}