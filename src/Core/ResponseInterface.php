<?php

namespace Rockberpro\RestRouter\Core;

interface ResponseInterface
{
    public function __construct($code, $status);
    public function response();
    public static function json($data, $status);
}