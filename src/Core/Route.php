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

    private static array $contextStack = [];
    private static array $currentContext = [
        'prefix'     => null,
        'namespace'  => null,
        'controller' => null,
        'middleware' => null,
    ];

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
        $route_path = rtrim($full_route, '/');

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        $instance = self::$instance;
        $instance->prefix = $prefix;
        $instance->route = $route_path;
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
        self::$currentContext['prefix'] = "/{$prefix}";

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

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
        self::$currentContext['namespace'] = $namespace;

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
        self::$currentContext['controller'] = $controller;

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
        self::$currentContext['middleware'] = $middleware;

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
        // push current context to stack
        self::$contextStack[] = self::$currentContext;
        var_dump(self::$contextStack);die;

        // clear current context
        self::clearContext();

        // execute route group
        $closure();

        // pop context from stack
        array_pop(self::$contextStack);
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
        // if target is a Closure, return it directly
        if ($target instanceof Closure) {
            return $target;
        }

        // if target is an array, return it directly
        if (is_array($target)) {
            return $target;
        }

        // if target is a string without '@', assume it's a method of the current controller
        if (is_string($target) && strpos($target, '@') === false) {
            $controller = self::$currentContext['controller'];
            if (!$controller) {
                throw new Exception('Controller not defined for the route.');
            }
            $method = $target;
            return [$controller, $method];
        }

        // if target is a string with '@', assume format Controller@method
        if (is_string($target) && strpos($target, '@') !== false) {
            list($controller, $method) = explode('@', $target, 2);
            $namespace = self::$currentContext['namespace'];
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

        // determine namespace and middleware for current context
        $namespace = self::$currentContext['namespace'];
        $middleware = self::$currentContext['middleware'];

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
    }

    /**
     * Builds the route path
     * 
     * @method route
     * @param string
     */
    private static function route($route): string
    {
        $prefixes = self::collectPrefixes();
        return self::PREFIX . implode('', $prefixes) . $route;
    }

    /**
     * Collect route prefixes from the context stack
     * 
     * @method routeStack
     * @return array
     */
    private static function collectPrefixes(): array
    {
        $prefixes = [];

        // Collect from stack
        foreach (self::$contextStack as $context) {
            if ($context['prefix'] !== null) {
                $prefixes[] = $context['prefix'];
            }
        }

        // Add current context prefix
        if (self::$currentContext['prefix'] !== null) {
            $prefixes[] = self::$currentContext['prefix'];
        }

        return $prefixes;
    }

    /**
     * Clears current context
     * 
     * @method clear
     * @return void
     */
    private function clearContext(): void
    {
      self::$currentContext = [
            'prefix' => null,
            'namespace' => null,
            'controller' => null,
            'middleware' => null,
        ];
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