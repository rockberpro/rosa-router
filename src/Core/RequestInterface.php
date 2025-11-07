<?php

namespace Rockberpro\RestRouter\Core;

interface RequestInterface
{
    public function handle(RequestData $requestData);
    public function setAction(RequestAction $action): void;
    public function getAction(): RequestAction;
    public function get($key);
}