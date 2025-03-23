<?php

namespace Rockberpro\RestRouter;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface ResponseInterface
{
    public function __construct($code, $status);
    public function response();
    public static function json($data, $status): never;
}