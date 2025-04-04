<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\RouteInterface;
use Closure;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class Route implements RouteInterface
{
    const PREFIX = '/api';

    private static ?string $namespace;
    private static ?string $controller;
    private static ?string $middleware;

    private static ?string $prefixHandler;

    private static array $groupPrefix = [];
    private static array $groupNamespace = [];
    private static array $groupController = [];
    private static array $groupMiddleware = [];

    private static self $instance;

    private string $prefix;
    private string $route;
    private string $method;
    private $target;

    /**
     * @method get
     * @param string $route
     * @param string|array|Closure $target
     */
    public static function get($route, $target): void
    {
        self::buildRoute('GET', $route, $target);
    }

    /**
     * @method post
     * @param string $route
     * @param string|array|Closure $target
     */
    public static function post($route, $target): void
    {
        self::buildRoute('POST', $route, $target);
    }

    /**
     * @method put
     * @param string $route
     * @param string|array|Closure $target
     */
    public static function put($route, $target): void
    {
        self::buildRoute('PUT', $route, $target);
    }

    /**
     * @method patch
     * @param string $route
     * @param string|array|Closure $target
     */
    public static function patch($route, $target): void
    {
        self::buildRoute('PATCH', $route, $target);
    }

    /**
     * @method delete
     * @param string $route
     * @param string|array|Closure $target
     */
    public static function delete($route, $target): void
    {
        self::buildRoute('DELETE', $route, $target);
    }

    /**
     * Build the route
     * 
     * @param mixed $method
     * @param mixed $route
     * @param mixed $target
     * @return void
     */
    private static function buildRoute($method, $route, $target): void
    {
        $_route = self::route($route);

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = $method;
        self::$instance->target = self::$instance->buildTarget($target);

        self::$instance->build();
    }

    /**
     * Adds prefix to the route group
     * 
     * @method prefix
     * @param $prefix
     * @return self
     */
    public static function prefix($prefix): Route
    {
        self::$prefixHandler = "/{$prefix}";
        self::$groupPrefix[] = "/{$prefix}";

        return new self();
    }

    /**
     * Adds prefix to the route group
     * 
     * @method namespace
     * @param string $namespace
     * @return self
     */
    public static function namespace($namespace): Route
    {
        self::$namespace = $namespace;

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Adds controller to the route
     * 
     * @method controller
     * @param string $controller classname
     * @return self
     */
    public static function controller($controller): Route
    {
        self::$controller = $controller;

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Adds middleware to the route
     * 
     * @method middleware
     * @param string $middleware classname
     * @return self
     */
    public static function middleware($middleware): Route
    {
        self::$middleware = $middleware;

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Group routes under the same prefix
     * 
     * @method group
     * @param string $prefix
     * @param closure $closure()
     */
    public function group($closure): void
    {
        /** group function has no prefix */
        if (!self::$prefixHandler) {
            self::$groupPrefix[] = null;
        }

        self::$groupNamespace[]  = self::$namespace  ?? end(self::$groupNamespace);
        self::$groupController[] = self::$controller ?? end(self::$groupController);
        self::$groupMiddleware[] = self::$middleware ?? end(self::$groupMiddleware);

        self::clear();

        $closure();

        array_pop(self::$groupPrefix);
        array_pop(self::$groupNamespace);
        array_pop(self::$groupController);
        array_pop(self::$groupMiddleware);
    }

    /**
     * Build the target for the route
     * 
     * @method buildTarget
     * @param string|array $target
     * @return array|Closure|string
     */
    private function buildTarget($target)
    {
        if ($target instanceof Closure) {
            return $target;
        }
        else if (gettype($target) === 'array') {
            return $target;
        }
        else if (gettype($target) === 'string' && stripos($target, '@') === false) {
            if (!isset(self::$controller))
            {
                $_controller = end(self::$groupController);
            }
            else {
                $_controller = self::$controller;
            }

            $controller = $_controller;
            $method = $target;
        }
        else if (stripos($target, '@') !== false) {
            $_namespace = self::$namespace ?? end(self::$groupNamespace);
            if (isset($_namespace)) {
                $namespace = $_namespace;
                $parts = explode('@', $target);

                $controller = $namespace.'\\'.$parts[0];
                $method = $parts[1];
            }
        }
        else {
            throw new Exception('Error trying to determine the route target');
        }

        return [$controller, $method];
    }

    /**
     * Building the route
     * 
     * @method private
     * @return void
     */
    private function build(): void
    {
        $route = [
            'prefix' => self::$instance->prefix,
            'route' => self::$instance->route,
            'target' => self::$instance->target
        ];

        if (!isset(self::$namespace))
        {
            $namespace = end(self::$groupNamespace);
            if ($namespace) {
                $route['namespace'] = $namespace;
            }
        }
        else {
            $route['namespace'] = self::$namespace;
        }

        if (!isset(self::$middleware))
        {
            $middleware = end(self::$groupMiddleware);
            if ($middleware) {
                $route['middleware'] = $middleware;
            }
        }
        else {
            $route['middleware'] = self::$middleware;
        }

        global $routes;
        $routes[self::$instance->method][] = $route;
    }

    /**
     * Builds the route path
     * 
     * @method route
     * @param string
     */
    private static function route($route): string
    {
        $_route = Route::PREFIX.$route;
        if (self::$groupPrefix) {
            $routeStack = self::routeStack();
            $_route = Route::PREFIX.implode($routeStack).$route;
        }

        return $_route;
    }

    /**
     * Get the route call stack
     * 
     * @method routeStack
     * @return array
     */
    private static function routeStack(): array
    {
        return array_filter(self::$groupPrefix, function($value) {
            return $value !== null;
        });
    }

    /**
     * Clears the route properties
     * 
     * @method clear
     * @return void
     */
    private function clear(): void
    {
        self::$prefixHandler = null;
        self::$namespace = null;
        self::$controller = null;
        self::$middleware = null;
    }

    /**
     * Get all routes
     * 
     * @method getRoutes
     * @return array
     */
    public static function getRoutes(): array
    {
        global $routes;
        return $routes;
    }
}