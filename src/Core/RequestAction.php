<?php

namespace Rockberpro\RestRouter\Core;

use Closure;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Core
 */
class RequestAction implements RequestActionInterface
{
    private ?string $middleware;
    private string $method;
    private Closure $closure;
    private array $route;
    private string $class;
    private string $uri;

    public function getMiddleware(): ?string {
        return $this->middleware ?? null;
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

    public function getClosure(): ?Closure {
        if (isset($this->closure)) {
            return $this->closure;
        }
        return null;
    }
    public function setClosure($closure): void {
        $this->closure = $closure;
    }

    public function getRoute(): array {
        return $this->route;
    }
    public function setRoute($route): void {
        $middleware = $route['middleware'] ?? null;
        if ($middleware) {
            $this->setMiddleware($middleware);
        }
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
        if (isset($this->closure)) {
            return is_callable($this->closure);
        }
        return false;
    }
}