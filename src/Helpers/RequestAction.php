<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Helpers\RequestActionInterface;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
class RequestAction implements RequestActionInterface
{
    private ?string $middleware = '';
    private string $method;
    private $closure;
    private array $route;
    private string $class;
    private string $uri;

    public function getMiddleware(): string {
        return $this->middleware;
    }
    public function setMiddleware($middleware): void {
        $this->middleware = $middleware;
    }

    public function getMethod(): string {
        return $this->method;
    }
    public function setMethod($method): void {
        $this->method = $method;
    }

    public function getClosure() {
        return $this->closure;
    }
    public function setClosure($closure): void {
        $this->closure = $closure;
    }

    public function getRoute(): array {
        return $this->route;
    }
    public function setRoute($route): void {
        $this->route = $route;
    }

    public function getClass(): string {
        return $this->class;
    }
    public function setClass($class): void {
        $this->class = $class;
    }

    public function getUri(): string {
        return $this->uri;
    }
    public function setUri($uri): void {
        $this->uri = $uri;
    }

    public function isClosure(): bool {
        return is_callable($this->closure);
    }
}