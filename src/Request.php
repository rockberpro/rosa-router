<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Interfaces\RequestInterface;
use Rockberpro\RestRouter\Helpers\DeleteRequest;
use Rockberpro\RestRouter\Helpers\GetRequest;
use Rockberpro\RestRouter\Helpers\PatchRequest;
use Rockberpro\RestRouter\Helpers\PostRequest;
use Rockberpro\RestRouter\Helpers\PutRequest;
use Rockberpro\RestRouter\Helpers\RequestAction;
use Rockberpro\RestRouter\Utils\UrlParser;
use Rockberpro\RestRouter\Utils\Json;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Database\Models\SysApiLogs;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class Request implements RequestInterface
{
    private RequestAction $action;

    private array $parameters = [];

    /**
     * Get the body data
     * 
     * @method body
     * @param bool $parse
     * @return array|bool|string|null
     */
    public static function body($parse = true): array|bool|string|null
    {
        $input = file_get_contents("php://input");

        if (Json::isJson($input))
            return (array) json_decode($input, true);

        if (!$parse)
            return $input;

        $data = [];
        parse_str($input, $data);
        if (empty($data))
            return null;

        return (array) $data;
    }

    /**
     * @method handle
     * @param string $method
     * @param string $uri
     * @param string $queryPath
     * @param array $body
     * @param array $queryParams
     */
    public function handle($method, $uri, $queryPath = null, $body = null, $queryParams = null)
    {
        global $routes;
        if ($routes === null)
            return new Response(['message' => 'No registered routes'], Response::NOT_FOUND);

        $path = UrlParser::path($uri);
        if ($queryPath) {
            $path = UrlParser::path($queryPath);
        }

        $segments = explode('/', $path ?? '');
        array_shift($segments);
        if ($segments[0] !== 'api') {
            return new Response(['message' => 'Not found'], Response::NOT_FOUND);
        }

        $request = null;
        switch ($method) {
            case 'GET':
                $request = (new GetRequest())->buildRequest($routes, $method, $path, $queryParams);
                break;
            case 'POST':
                $request = (new PostRequest())->buildRequest($routes, $method, $path, $body, $queryParams);
                break;
            case 'PUT':
                $request = (new PutRequest())->buildRequest($routes, $method, $path, $body, $queryParams);
                break;
            case 'PATCH':
                $request = (new PatchRequest())->buildRequest($routes, $method, $path, $body, $queryParams);
                break;
            case 'DELETE':
                $request = (new DeleteRequest())->buildRequest($routes, $method, $path, $queryParams);
                break;
            default: break;
        }

        if ($request === null)
            throw new Exception('It was not possible to match your request');

        $this->writeLog($request);

        $closure = $request->getAction()->getClosure();
        if ($closure) {
            $response = $closure($request); /// Response
            return $response;
        }

        $class = $request->getAction()->getClass();
        $method = $request->getAction()->getMethod();

        /** call the controller */
        $response = (new $class)->$method($request);
        return $response;
    }

    /**
     * Write the request log
     * 
     * @method writeLog
     * @param Request $request
     * @return void
     */
    private function writeLog(Request $request): void
    {
        if (DotEnv::get('API_LOGS')) {
            try {
                SysApiLogs::write($request);
            }
            catch(Exception $e) {
                throw new Exception("It was not possible to write the request log: {$e->getMessage()}");
            }
        }
    }

    /**
     * Set the route action
     * 
     * @method setAction
     * @param RequestAction $action
     * @return void
     */
    public function setAction(RequestAction $action): void
    {
        $this->action = $action;
    }

    /**
     * Get the route action
     * 
     * @method getAction
     * @return RequestAction
     */
    public function getAction(): RequestAction
    {
        return $this->action;
    }

    /**
     * Get all route parameters
     * 
     * @method getParameters
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get a route variable
     * 
     * @method get
     * @param string $key
     * @param string $value
     */
    public function get($key)
    {
        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        return null;
    }

    /**
     * Set the route parameters
     * 
     * @method parameters
     * @return array
     */
    public function __set($key, $value)
    {
        return $this->parameters[$key] = $value;
    }

    /**
     * Get the route parameters
     * 
     * @method parameters
     * @return array
     */
    public function __get($key)
    {
        return $this->parameters[$key];
    }
}