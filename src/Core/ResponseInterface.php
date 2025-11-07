<?php

namespace Rockberpro\RosaRouter\Core;

interface ResponseInterface
{
    public function __construct($code, $status);
    public function response();
    public static function json($data, $status);
}