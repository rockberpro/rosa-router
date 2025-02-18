<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Helpers\Interfaces\RequestActionInterface;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
class RequestAction implements RequestActionInterface
{
    private $middleware;
    private $method;
    private $closure;
    private $route;
    private $class;
    private $uri;

    public function getMiddleware() {
        return $this->middleware;
    }
    public function setMiddleware($middleware) {
        $this->middleware = $middleware;
    }

    public function getMethod() {
        return $this->method;
    }
    public function setMethod($method) {
        $this->method = $method;
    }

    public function getClosure() {
        return $this->closure;
    }
    public function setClosure($closure) {
        $this->closure = $closure;
    }

    public function getRoute() {
        return $this->route;
    }
    public function setRoute($route) {
        $this->route = $route;
    }

    public function getClass() {
        return $this->class;
    }
    public function setClass($class) {
        $this->class = $class;
    }

    public function getUri() {
        return $this->uri;
    }
    public function setUri($uri) {
        $this->uri = $uri;
    }
}