<?php

namespace Rockberpro\RestRouter\Core;

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
     * @method head
     * @param string $route
     * @param string|array|Closure $target
     */
    public static function head($route, $target): void
    {
        self::buildRoute('HEAD', $route, $target);
    }

    /**
     * @method options
     * @param string $route
     * @param string|array|Closure $target
     */
    public static function options($route, $target): void
    {
        self::buildRoute('OPTIONS', $route, $target);
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

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        $instance = self::$instance;
        $instance->prefix = $prefix;
        $instance->route =  $full_route;
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
     * Adds namespace to the route group
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
     * Group routes under the same context
     * 
     * @method group
     * @param closure $closure()
     */
    public function group($closure): void
    {
        // push current context to stack
        self::$contextStack[] = self::$currentContext;

        // reset context for the group scope
        self::$currentContext = [
            'prefix'     => null,
            'namespace'  => null,
            'controller' => null,
            'middleware' => null,
        ];

        // execute route group
        $closure();

        // pop context from stack
        array_pop(self::$contextStack);

        // clear current context completely after exiting group
        self::$currentContext = [
            'prefix'     => null,
            'namespace'  => null,
            'controller' => null,
            'middleware' => null,
        ];
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

        // if target is a string without '@', use current controller
        if (is_string($target) && strpos($target, '@') === false) {
            $controller = self::getContextValue('controller');
            if (!$controller) {
                throw new Exception('Controller not defined for the route.');
            }
            return [$controller, $target];
        }

        // if target is a string with '@', parse Controller@method
        if (is_string($target) && strpos($target, '@') !== false) {
            list($controller, $method) = explode('@', $target, 2);
            $namespace = self::getContextValue('namespace');
            if ($namespace) {
                $controller = $namespace . '\\' . $controller;
            }
            return [$controller, $method];
        }

        throw new Exception('Invalid or unsupported route target.');
    }

    /**
     * Get a context value with inheritance from parent contexts
     * 
     * @param string $key
     * @return mixed|null
     */
    private static function getContextValue(string $key)
    {
        // check current context first
        if (self::$currentContext[$key] !== null) {
            return self::$currentContext[$key];
        }

        // walk back through the stack to find the value (inheritance)
        for ($i = sizeof(self::$contextStack) - 1; $i >= 0; $i--) {
            if (self::$contextStack[$i][$key] !== null) {
                return self::$contextStack[$i][$key];
            }
        }

        return null;
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
          'method' => self::$instance->method,
          'prefix' => self::$instance->prefix,
          'route'  => self::$instance->route,
          'target' => self::$instance->target,
        ];

        // get values with inheritance
        $namespace = self::getContextValue('namespace');
        $middleware = self::getContextValue('middleware');

        if ($namespace) {
            $route['namespace'] = $namespace;
        }
        if ($middleware) {
            $route['middleware'] = $middleware;
        }

        $this->createRoute($route);
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
     * @method collectPrefixes
     * @return array
     */
    private static function collectPrefixes(): array
    {
        $prefixes = [];

        // collect from stack
        foreach (self::$contextStack as $context) {
            if ($context['prefix'] !== null) {
                $prefixes[] = $context['prefix'];
            }
        }

        // add current context prefix
        if (self::$currentContext['prefix'] !== null) {
            $prefixes[] = self::$currentContext['prefix'];
        }

        return $prefixes;
    }

    /**
     * @param array $route
     * @return void
     */
    private function createRoute(array $route): void
    {
        $routes = Server::getInstance()->getRoutes();
        $routes[self::$instance->method][] = $route;
        Server::getInstance()->setRoutes($routes);
    }

    /**
     * Get all routes
     * 
     * @method getRoutes
     * @return array
     */
    public static function getRoutes(): array
    {
        return Server::getInstance()->getRoutes();
    }
}