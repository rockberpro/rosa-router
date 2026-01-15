<?php

namespace Rockberpro\RosaRouter\Core;

use Rockberpro\RosaRouter\Helpers\RouteHelper;
use Closure;

abstract class AbstractRequest implements AbstractRequestInterface
{
    /**
     * Build the request
     * 
     * @method buildRequest
     * @param RequestData $data
     * @return Request
     */
    public function buildRequest(RequestData $data): Request
    {
        $routes = $this->getRoutesForMethod(RouteHandler::getInstance()->getRoutes(), $data->getMethod());

        $request = new Request();
        $action = $this->handle($routes, $data->getUri());
        $request->setData($data);
        $request->setAction($action);

        $request = $this->pathParams($request);
        $request = $this->queryParams($request, $data->getQueryParams());

        return $request;
    }

    /**
     * Handle the Request
     * 
     * @method handle
     * @param array $routes
     * @param string $uri
     * @return RequestAction
     */
    public function handle($routes, $uri): RequestAction
    {
        // normalize URI to treat trailing slash as optional ("/foo" and "/foo/" both match)
        $uri = $this->normalizeUri($uri);

        $routes_map = $this->map($routes, $uri);
        $match = $this->match($routes, $routes_map, $uri);
        if (!$match) {
            throw new RequestException('No matching route');
        }

        return $this->buildAction($uri, $match);
    }

    /**
     * Normalize URI by removing a trailing slash except for the root '/'
     *
     * @param string $uri
     * @return string
     */
    private function normalizeUri(string $uri): string
    {
        if ($uri === '/') {
            return $uri;
        }

        $trimmed = rtrim($uri, '/');

        return $trimmed === '' ? '/' : $trimmed;
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
            throw new RequestException('Route does not match: different number of segments');
        }

        foreach ($route_parts as $key => $route_part) {
            $uri_part = $uri_parts[$key] ?? null;

            // check if it's a parameter (e.g., {id})
            $route_matches = [];
            if ($this->checkIfPathParam($route_part, $route_matches)) {
                $path_arg = $this->pathArg($route_matches);
                if (!isset($uri_part) || !RouteHelper::isAlphaNumeric($uri_part)) {
                    throw new RequestException("Invalid or missing value for route parameter: {$path_arg}");
                }
                $request->setPathParam($path_arg, $uri_part);
            }
            else {
                // static segment must match exactly
                if ($route_part !== $uri_part) {
                    throw new RequestException("Route segment mismatch: expected '{$route_part}', got '{$uri_part}'");
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
            $request->setQueryParam($key, $value);
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
        $route = $request->getAction()->getRoute()['route'];
        $uri = $request->getAction()->getUri();

        $uri_parts = explode('/', $uri);
        $route_parts = explode('/', $route);

        return [
            'route_parts' => $route_parts,
            'uri_parts' => $uri_parts
        ];
    }

    /**
     * Build the action
     * 
     * @method buildAction
     * @param string $uri
     * @param array $match
     * @return RequestAction
     */
    private function buildAction($uri, $match): RequestAction
    {
        $action = new RequestAction();
        $action->setUri($uri);
        $action->setRoute($match);

        $target = $action->getRoute()['target'];
        if (!$target) {
            throw new RequestException('Target not defined for the route.');
        }
        if ($target instanceof Closure) {
            $action->setClosure($target);
        }
        elseif (is_array($target) && sizeof($target) === 2) {
            [$class, $method_name] = $target;
            if (!class_exists($class)) {
                throw new RequestException("Class not found: {$class}");
            }
            if (!method_exists($class, $method_name)) {
                throw new RequestException("Method not found: {$method_name} in {$class}");
            }
            $action->setClass($class);
            $action->setMethod($method_name);
        }
        else {
            throw new RequestException('Invalid route target.');
        }

        return $action;
    }

    /**
     * Map the routes
     * 
     * @method map
     * @param array $routes
     * @param string $uri
     * @return array mapped_routes
     */
    public function map($routes, $uri): array
    {
        $filtered_routes = array_filter(
            $routes,
            function ($route) use ($uri) {
                if (!isset($route['prefix']) || !isset($route['route'])) {
                    return false;
                }
                /* map to return only the route value */
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
     * @param array $routes
     * @param string $method
     * @param string $uri
     * @return array
     */
    public function match($routes, $mapped_routes, $uri): array
    {
        $filtered = array_filter(
            $mapped_routes,
            function($route) use ($uri) {
                return $this->matchCondition($route, $uri);
            }
        );
        $match = reset($filtered);

        $macthing_route = array_filter($routes, function($route) use ($match) {
            return $route['route'] === $match;
        });
        $macthing_route = array_shift($macthing_route);
        if (!$macthing_route) {
            throw new RequestException('No matching route found.');
        }

        return $macthing_route;
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

    /**
     * @param array $all_routes
     * @param string $method
     * @return mixed
     * @throws RequestException
     */
    public function getRoutesForMethod(array $all_routes, string $method): array
    {
        if (!$all_routes) {
            throw new RequestException("No routes defined for the given method: {$method}");
        }
        if (!array_key_exists($method, $all_routes)) {
            throw new RequestException("No routes defined for the given method: {$method}");
        }
        $routes = $all_routes[$method];

        return $routes;
    }

    /**
     * @param string $route_part
     * @param array $route_matches
     * @return false|int
     */
    public function checkIfPathParam(string $route_part, array &$route_matches)
    {
        return preg_match('/^{(\w+)}$/', $route_part, $route_matches);
    }

    /**
     * @param array $route_matches
     * @return string
     */
    public function pathArg(array $route_matches): string
    {
        $part = array_slice($route_matches, 1, 1);
        $route_arg = reset($part);
        return $route_arg;
    }
}