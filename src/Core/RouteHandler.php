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
        $routes = self::getInstance()->getRoutes();
        $routes[$method][] = $route;
        self::getInstance()->setRoutes($routes);
    }

    /**
     * @param array $routes
     * @return void
     */
    private function setRoutes(array $routes): void
    {
        self::getInstance()->routes = $routes;
    }

    /**
     * Get all routes
     *
     * @method getRoutes
     * @return array
     */
    public function getRoutes(): array
    {
        return self::getInstance()->routes;
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
}