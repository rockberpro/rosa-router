<?php

namespace Rockberpro\RosaRouter\Core;

/**
 * Handles the management of the API Routes
 */
class RouteHandler
{
    /**
     * @var self|null
     */
    private static ?self $instance = null;

    private array $routes = [];

    /**
     * @param array $route
     * @return void
     */
    public function addRoute($method, array $route): void
    {
        $this->routes[$method][] = $route;
    }

    /**
     * Get all routes
     *
     * @method getRoutes
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Singleton
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Drop the singleton (and with it every registered route).
     *
     * Provides a state-reset seam for tests and for long-running / stateful
     * (ReactPHP) hosts where the process-global registry would otherwise leak
     * across requests.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}