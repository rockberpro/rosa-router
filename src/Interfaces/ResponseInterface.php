<?php

namespace Rockberpro\RestRouter\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface ResponseInterface
{
    public function __construct($code, $status);
    public function response(): void;
    public static function json($data, $status): never;
}