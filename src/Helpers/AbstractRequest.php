<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Helpers\AbstractRequestInterface;
use Rockberpro\RestRouter\RequestData;
use Rockberpro\RestRouter\Request;
use Closure;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
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
     * @param array $route_parts
     * @param array $uri_parts
     * @return Request
     */
    private function pathParams(Request &$request): Request
    {
        $parts = $this->getRouteParts($request);
        [$route_parts, $uri_parts] = [$parts['route_parts'], $parts['uri_parts']];
        foreach($route_parts as $key => $value)
        {
            $attribute = substr($value, 1, -1);
            if (isset($uri_parts[$key])) {
                if ($value === $uri_parts[$key]) {
                    continue;
                }
                if (stripos($value, '{') === false || stripos($value, '}') === false) {
                    if ($value !== $uri_parts[$key]) {
                        throw new Exception('Route does not match');
                    }
                }
                if (!RouteHelper::isAlphaNumeric($uri_parts[$key])) {
                    throw new Exception('Route contains invalid characters');
                }
                $request->$attribute = $uri_parts[$key];
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

        if (array_key_exists(array_key_first($action->getRoute()), $routes[$method])) {
            $call = $routes[$method][array_key_first($action->getRoute())];

            if ($call['target'] instanceof Closure) {
                $action->setClosure($call['target']);
            }
            else if (gettype($call['target']) === 'array') {
                $class = $call['target'][0];
                $method = $call['target'][1];
                if (!class_exists($class)) {
                    throw new Exception("Class not found: {$class}");
                }
                if (!method_exists($class, $method)) {
                    throw new Exception("Method not found: {$method}");
                }
                $action->setClass($class);
                $action->setMethod($method);
            }
            else {
                throw new Exception('Invalid route target');
            }
        }
        else {
            throw new Exception('No method defined for the route');
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
        if (!isset($routes[$method]))
            throw new Exception("No routes for method {$method}");

        $filter = array_filter(
            $routes[$method],
            function($route) use (&$uri) {
                $parts = explode($route['prefix'], $uri);
                if ($parts[0] === '') {
                    return $route['route'];
                }
            }
        );

        $map = array_map(
            function($route) {
                return $route['route'];
            },
            $filter
        );
   
        return $map;
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

        $_route_sufixes = explode($prefix, $route);
        $route_sufixes = explode('/', end($_route_sufixes));

        $_uri_sufixes = explode($prefix, $uri)[1];
        $uri_sufixes = explode('/', $_uri_sufixes);

        if (stripos($uri, $prefix) !== false) {
            $route_parts = explode('/', $route);
            $uri_parts = explode('/', $uri);

            if (sizeof($uri_parts) === sizeof($route_parts)) {

                if (sizeof($uri_sufixes) === sizeof($route_sufixes)) {

                    $diff = array_diff($uri_parts, $route_parts);

                    /** when route has only prefix, not param nor suffix */
                    if (
                       sizeof($diff) === sizeof($uri_sufixes)
                    && end($route_parts) !== end($uri_parts)
                    && !in_array(end($uri_parts), $route_parts)
                    && ( stripos(end($route_parts), '{') !== false && stripos(end($route_parts), '}') !== false )
                    ) {
                        return true;
                    }

                    /** when route has suffix */
                    if (
                       sizeof($diff) !== sizeof($uri_sufixes)
                    && end($route_parts) === end($uri_parts)
                    && in_array(end($uri_parts), $route_parts)
                    && ( stripos(end($route_parts), '{') === false && stripos(end($route_parts), '}') === false )
                    ) {
                        return true;
                    }

                    /** when prefix and param have the same value */
                    if (
                       sizeof($diff) !== sizeof($uri_sufixes)
                    && end($route_parts) !== end($uri_parts)
                    && in_array(end($uri_parts), $route_parts)
                    && ( stripos(end($route_parts), '{') !== false && stripos(end($route_parts), '}') !== false )
                    ) {
                        return true;
                    }

                    return false;
                }

                return false;
            }

            return false;
        }

        return false;
    }
}