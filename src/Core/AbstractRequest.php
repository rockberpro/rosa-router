<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\AbstractRequestInterface;
use Rockberpro\RestRouter\Helpers\RouteHelper;
use Rockberpro\RestRouter\RequestData;
use Rockberpro\RestRouter\Request;
use Closure;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Core
 */
abstract class AbstractRequest implements AbstractRequestInterface
{
    /**
     * Build the request from the URL
     * 
     * @method buildUriRequest
     * @param array $routes
     * @param RequestData $requestData
     * @return Request
     */
    public function buildUriRequest($routes, RequestData $requestData): Request
    {
        $request = new Request();
        $request->setAction($this->handle($routes, $requestData->getMethod(), $requestData->getUri()));

        $request = $this->pathParams($request);
        $request = $this->queryParams($request, $requestData->getQueryParams());

        if ($middleware = $request->getAction()->getMiddleware()) {
            $this->middleware($middleware, $request);
        }

        return $request;
    }

    /**
     * Build the request from form data
     * 
     * @method buildBodyRequest
     * @param array $routes
     * @param RequestData $requestData
     * @return Request
     */
    public function buildBodyRequest($routes, RequestData $requestData): Request
    {
        $request = new Request();
        $request->setAction($this->handle($routes, $requestData->getMethod(), $requestData->getUri()));

        $request = $this->pathParams($request);
        $request = $this->queryParams($request, $requestData->getQueryParams());

        /** form params */
        foreach((array) $requestData->getBody() as $key => $value) {
            $request->$key = $value;
        }

        if ($middleware = $request->getAction()->getMiddleware()) {
            $this->middleware($middleware, $request);
        }

        return $request;
    }

    /**
     * Handle the Request
     * 
     * @method handle
     * @param array $routes
     * @param string $method
     * @param string $uri
     * @return RequestAction
     */
    public function handle($routes, $method, $uri): RequestAction
    {
        $routes_map = $this->map($routes, $method, $uri);
        $match = $this->match($routes_map, $uri);
        if (empty($match)) {
            throw new Exception('No matching route');
        }

        $action = $this->buildAction($routes, $method, $uri, $match);
        $match = $routes[$method][array_key_first($action->getRoute())];

        /** middleware */
        if (isset($match['middleware'])) {
            $action->setMiddleware($match['middleware']);
        }

        return $action;
    }

    /**
     * Handle the path params
     *
     * @method pathParams
     * @param Request $request
     * @return Request
     */
    private function pathParams(Request &$request): Request
    {
        $parts = $this->getRouteParts($request);
        [$route_parts, $uri_parts] = [$parts['route_parts'], $parts['uri_parts']];

        if (sizeof($route_parts) !== sizeof($uri_parts)) {
            throw new Exception('Route does not match: different number of segments');
        }

        foreach ($route_parts as $key => $route_part) {
            $uri_part = $uri_parts[$key] ?? null;

            $matches = [];
            /* Check if it's a parameter (e.g., {id}) */
            if (preg_match('/^{(\w+)}$/', $route_part, $matches)) {
                $attribute = $matches[1];
                if (!isset($uri_part) || !RouteHelper::isAlphaNumeric($uri_part)) {
                    throw new Exception("Invalid or missing value for route parameter: {$attribute}");
                }
                $request->$attribute = $uri_part;
            }
            else {
                /* Static segment must match exactly */
                if ($route_part !== $uri_part) {
                    throw new Exception("Route segment mismatch: expected '{$route_part}', got '{$uri_part}'");
                }
            }
        }

        return $request;
    }

    /**
     * Handle the query params
     * 
     * @method queryParams
     * @param Request $request
     * @param array $queryParams
     * @return Request
     */
    private function queryParams(Request &$request, $queryParams): Request
    {
        foreach ($queryParams as $key => $value) {
            $request->$key = $value;
        }

        return $request;
    }

    /**
     * Build the route parts
     * 
     * @method getRouteParts
     * @param Request $request
     * @return array [
     *   'route_parts' => [ * ],
     *   'uri_parts' =>  [ * ]
     * ]
     */
    private function getRouteParts(Request $request): array
    {
        $route = $request->getAction()->getRoute();
        $route = end($route);
        $prefix = RouteHelper::routeMatchArgs($route)[0];

        $_uri = str_replace($prefix, '', $request->getAction()->getUri());
        $_route = str_replace($prefix, '', $route);

        $uri_parts = explode('/', $_uri);
        $route_parts = explode('/', $_route);

        return [
            'route_parts' => $route_parts,
            'uri_parts' => $uri_parts
        ];
    }

    /**
     * Build the action
     * 
     * @method buildAction
     * @param array $routes
     * @param string $method
     * @param string $uri
     * @param array $match
     * @return RequestAction
     */
    private function buildAction($routes, $method, $uri, $match): RequestAction
    {
        $action = new RequestAction();
        $action->setUri($uri);
        $action->setRoute($match);

        $routeKeys = array_keys($action->getRoute());
        if (empty($routeKeys)) {
            throw new Exception('No route found for the given method.');
        }

        $routeKey = $routeKeys[0];

        if (!isset($routes[$method][$routeKey])) {
            throw new Exception('No route defined for the given method and key.');
        }

        $call = $routes[$method][$routeKey];

        if (!isset($call['target'])) {
            throw new Exception('Target not defined for the route.');
        }

        $target = $call['target'];

        if ($target instanceof Closure) {
            $action->setClosure($target);
        }
        elseif (is_array($target) && sizeof($target) === 2) {
            [$class, $methodName] = $target;
            if (!class_exists($class)) {
                throw new Exception("Class not found: {$class}");
            }
            if (!method_exists($class, $methodName)) {
                throw new Exception("Method not found: {$methodName} in {$class}");
            }
            $action->setClass($class);
            $action->setMethod($methodName);
        }
        else {
            throw new Exception('Invalid route target.');
        }

        return $action;
    }

    /**
     * Middleware
     * 
     * @method middleware
     * @param string $middleware
     * @param Request $request
     * @return void
     */
    private function middleware($middleware, Request $request): void
    {
        if (!class_exists($middleware)) {
            throw new Exception("Middleware not found: {$middleware}");
        }
        if (!method_exists($middleware, 'handle')) {
            throw new Exception("Method 'handle' nod implemented for middleware: {$middleware}");
        }
        $middleware = new $middleware();
        $middleware->handle($request);
    }

    /**
     * Map the routes
     * 
     * @method map
     * @param array $routes
     * @param string $method
     * * @param string $uri
     * @return array mapped_routes
     */
    public function map($routes, $method, $uri): array
    {
        if (!isset($routes[$method])) {
            throw new Exception("No routes for method {$method}");
        }

        $filtered_routes = array_filter(
            $routes[$method],
            function ($route) use ($uri) {
                if (!isset($route['prefix']) || !isset($route['route'])) {
                    return false;
                }
                /* Map to return only the route value */
                return strpos($uri, $route['prefix']) === 0;
            }
        );

        $mapped_routes = array_map(
            function ($route) {
                return $route['route'];
            },
            $filtered_routes
        );

        return $mapped_routes;
    }

    /**
     * Find the matching route
     * 
     * @method match
     * @param array $mapped_routes
     * @param string $method
     * @param string $uri
     * @return array
     */
    public function match($mapped_routes, $uri): array
    {
        return array_filter(
            $mapped_routes,
            function($route) use ($uri) {
                return $this->matchCondition($route, $uri);
            }
        );
    }

    /**
     * Match the route if attends the condition
     * 
     * @method matchCondition
     * @param string $route
     * @param string $uri
     * @return bool
     */
    private function matchCondition($route, $uri): bool
    {
        $prefix = RouteHelper::routeMatchArgs($route)[0];

        if (stripos($uri, $prefix) === false) {
            return false;
        }

        $route_parts = explode('/', $route);
        $uri_parts = explode('/', $uri);

        if (sizeof($route_parts) !== sizeof($uri_parts)) {
            return false;
        }

        return !array_diff(
            array_filter(
                $route_parts,
                function($part) {
                    return (
                           stripos($part, '{') === false
                        && stripos($part, '}') === false
                    );
                }
            ),
            $uri_parts
        );
    }
}