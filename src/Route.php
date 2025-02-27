<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Interfaces\RouteInterface;
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
     * @param string $target
     * @return self
     */
    public static function get($route, $target)
    {
        $_route = self::route($route);

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'GET';
        self::$instance->target = self::$instance->buildTarget($target);

        self::$instance->build();
    }

    /**
     * @method post
     * @param string $route
     * @param string $target
     * @return self
     */
    public static function post($route, $target)
    {
        $_route = self::route($route);

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'POST';
        self::$instance->target = self::$instance->buildTarget($target);

        self::$instance->build();
    }

    /**
     * @method put
     * @param string $route
     * @param string $target
     * @return self
     */
    public static function put($route, $target)
    {
        $_route = self::route($route);

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'PUT';
        self::$instance->target = self::$instance->buildTarget($target);

        self::$instance->build();
    }

    /**
     * @method patch
     * @param string $route
     * @param string $target
     * @return self
     */
    public static function patch($route, $target)
    {
        $_route = self::route($route);

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'PATCH';
        self::$instance->target = self::$instance->buildTarget($target);

        self::$instance->build();
    }

    /**
     * @method delete
     * @param string $route
     * @param string $target
     * @return self
     */
    public static function delete($route, $target)
    {
        $_route = self::route($route);

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'DELETE';
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
    public static function prefix($prefix)
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
    public static function namespace($namespace)
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
    public static function controller($controller)
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
    public static function middleware($middleware)
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
     * @param function $closure()
     */
    public function group($closure)
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
     * @return array
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
     * @param void
     * @return void
     */
    private function build()
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
     * @param void
     */
    private static function route($route)
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
     * @param void
     * @return array
     */
    private static function routeStack()
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
    private function clear()
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
     * @param void
     * @return array
     */
    public static function getRoutes()
    {
        global $routes;
        return $routes;
    }
}