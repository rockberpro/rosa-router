<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Interfaces\RouteInterface;
use Closure;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @version 1.0
 * @package Rockberpro\RestRouter
 */
class Route implements RouteInterface
{
    const PREFIX = '/api';

    private bool $isChained = false;

    private static $namespace;
    private static $controller;
    private static $middleware;

    private static $groupPrefix = [];
    private static $groupNamespace = [];
    private static $groupController = [];
    private static $groupMiddleware = [];

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
        
        $_route = Route::PREFIX.$route;
        if (self::$groupPrefix) {
            $_route = Route::PREFIX.implode(self::$groupPrefix).$route;
        }
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'GET';
        self::$instance->target = self::buildTarget($target);

        self::$instance->build();

        return self::$instance;
    }

    /**
     * @method post
     * @param string $route
     * @param string $target
     * @return self
     */
    public static function post($route, $target)
    {
        $_route = Route::PREFIX.$route;
        if (self::$groupPrefix) {
            $_route = Route::PREFIX.implode(self::$groupPrefix).$route;
        }
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'POST';
        self::$instance->target = self::buildTarget($target);

        self::$instance->build();

        return self::$instance;
    }

    /**
     * @method put
     * @param string $route
     * @param string $target
     * @return self
     */
    public static function put($route, $target)
    {
        $_route = Route::PREFIX.$route;
        if (self::$groupPrefix) {
            $_route = Route::PREFIX.implode(self::$groupPrefix).$route;
        }
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'PUT';
        self::$instance->target = self::buildTarget($target);

        self::$instance->build();

        return self::$instance;
    }

    /**
     * @method patch
     * @param string $route
     * @param string $target
     * @return self
     */
    public static function patch($route, $target)
    {
        $_route = Route::PREFIX.$route;
        if (self::$groupPrefix) {
            $_route = Route::PREFIX.implode(self::$groupPrefix).$route;
        }
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'PATCH';
        self::$instance->target = self::buildTarget($target);

        self::$instance->build();

        return self::$instance;
    }

    /**
     * @method delete
     * @param string $route
     * @param string $target
     * @return self
     */
    public static function delete($route, $target)
    {
        $_route = Route::PREFIX.$route;
        if (self::$groupPrefix) {
            $_route = Route::PREFIX.implode(self::$groupPrefix).$route;
        }
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->prefix = explode('{', $_route)[0];
        self::$instance->route = $_route;
        self::$instance->method = 'DELETE';
        self::$instance->target = self::buildTarget($target);

        self::$instance->build();

        return self::$instance;
    }

    /**
     * Allows chaining of route methods
     * 
     * @method chain
     * @return self
     */
    public function chain()
    {
        $this->isChained = true;

        return $this;
    }

    /**
     * Ends the route method chaining
     * 
     * @method end
     * @return void
     */
    public function end()
    {
        $this->isChained = false;

        self::$namespace = null;
        self::$controller = null;
        self::$middleware = null;
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
        self::$groupNamespace[]  = self::$namespace  ?? end(self::$groupNamespace);
        self::$groupController[] = self::$controller ?? end(self::$groupController);
        self::$groupMiddleware[] = self::$middleware ?? end(self::$groupMiddleware);

        self::$namespace = null;
        self::$controller = null;
        self::$middleware = null;

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
    private static function buildTarget($target)
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

        if (!$this->isChained) {
            self::$namespace = null;
            self::$controller = null;
            self::$middleware = null;            
        }

        global $routes;
        $routes[self::$instance->method][] = $route;
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