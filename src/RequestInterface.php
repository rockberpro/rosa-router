<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Helpers\RequestAction;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface RequestInterface
{
    public static function body(): array|bool|string|null;
    public function handle(RequestData $requestData);
    public function setAction(RequestAction $action): void;
    public function getAction(): RequestAction;
    public function get($key);
}