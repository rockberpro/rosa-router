<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\RouteInterface;
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
     * @param string $method
     * @param string $route
     * @param string|array|Closure $target
     * @return void
     */
    private static function buildRoute($method, $route, $target): void
    {
        if (!is_string($method) || empty($method)) {
            throw new Exception('HTTP invalid method.');
        }
        if (!is_string($route) || empty($route)) {
            throw new Exception('Route invalid.');
        }
        if (empty($target)) {
            throw new Exception('Target route cannot be empty.');
        }

        $full_route = self::route($route);
        $prefix = rtrim(explode('{', $full_route)[0], '/');
        $routePath = rtrim($full_route, '/');

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        $instance = self::$instance;
        $instance->prefix = $prefix;
        $instance->route = $routePath;
        $instance->method = strtoupper($method);
        $instance->target = $instance->buildTarget($target);

        $instance->build();
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

        /* stack route context */
        self::$groupNamespace[]  = self::$namespace  ?? end(self::$groupNamespace);
        self::$groupController[] = self::$controller ?? end(self::$groupController);
        self::$groupMiddleware[] = self::$middleware ?? end(self::$groupMiddleware);

        /* clear static properties to avoid context leakage */
        self::clear();

        /* execute route group context */
        $closure();

        /* unstack route context */
        array_pop(self::$groupPrefix);
        array_pop(self::$groupNamespace);
        array_pop(self::$groupController);
        array_pop(self::$groupMiddleware);
    }

    /**
     * Build the target for the route
     * 
     * @method buildTarget
     * @param string|array|Closure $target
     * @return array|Closure|string
     */
    private function buildTarget($target)
    {
        /* if target is a Closure, return it directly */
        if ($target instanceof Closure) {
            return $target;
        }

        /* if target is an array, return it directly */
        if (is_array($target)) {
            return $target;
        }

        /* if target is a string without '@', assume it's a method of the current controller */
        if (is_string($target) && strpos($target, '@') === false) {
            $controller = self::$controller ?? end(self::$groupController);
            if (!$controller) {
                throw new Exception('Controller not defined for the route.');
            }
            $method = $target;
            return [$controller, $method];
        }

        /* if target is a string with '@', assume format Controller@method */
        if (is_string($target) && strpos($target, '@') !== false) {
            list($controller, $method) = explode('@', $target, 2);
            $namespace = self::$namespace ?? end(self::$groupNamespace);
            if ($namespace) {
                $controller = $namespace . '\\' . $controller;
            }
            return [$controller, $method];
        }

        throw new Exception('Invalid or unsupported route target.');
    }

    /**
     * Building the route
     * 
     * @method private
     * @return void
     */
    private function build(): void
    {
        global $routes;
        if (!isset($routes) || !is_array($routes)) {
            $routes = [];
        }

        /* determine namespace and middleware for current context */
        $namespace = self::$namespace ?? end(self::$groupNamespace) ?: null;
        $middleware = self::$middleware ?? end(self::$groupMiddleware) ?: null;

        $route = [
            'method'     => self::$instance->method,
            'prefix'     => self::$instance->prefix,
            'route'      => self::$instance->route,
            'target'     => self::$instance->target,
        ];
        if ($namespace) {
            $route['namespace'] = $namespace;
        }
        if ($middleware) {
            $route['middleware'] = $middleware;
        }

        $routes[self::$instance->method][] = $route;

        /* clear static properties to avoid context leakage */
        self::clear();
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