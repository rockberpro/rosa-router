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
     * Get the form data
     * 
     * @method form
     * @param bool $parse
     * @return array|bool|string|null
     */
    public static function form($parse = true): array|bool|string|null
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
     * @param string $query
     * @param array $form
     */
    public function handle($method, $uri, $query = null, $form = null): void
    {
        global $routes;
        if ($routes === null)
            Response::json(['message' => 'No registered routes'], Response::NOT_FOUND);

        $path = UrlParser::path($uri);
        if ($query) {
            $path = UrlParser::path($query);
        }

        $segments = explode('/', $path ?? '');
        array_shift($segments);
        if ($segments[0] !== 'api') {
            Response::json(['message' => 'Not found'], Response::NOT_FOUND);
        }

        $request = null;
        switch ($method) {
            case 'GET':
                $request = (new GetRequest())->buildRequest($routes, $method, $path);
                break;
            case 'POST':
                $request = (new PostRequest())->buildRequest($routes, $method, $path, $form);
                break;
            case 'PUT':
                $request = (new PutRequest())->buildRequest($routes, $method, $path, $form);
                break;
            case 'PATCH':
                $request = (new PatchRequest())->buildRequest($routes, $method, $path, $form);
                break;
            case 'DELETE':
                $request = (new DeleteRequest())->buildRequest($routes, $method, $path);
                break;
            default: break;
        }

        if (is_null($request))
            throw new Exception('It was not possible to match your request');

        $this->writeLog($request);

        $closure = $request->getAction()->getClosure();
        if ($closure) {
            $closure($request);
            return;
        }

        $class = $request->getAction()->getClass();
        $method = $request->getAction()->getMethod();

        /** call the controller */
        $response = (new $class)->$method($request);
        if (method_exists($response, 'response')) {
            $response->response();
        }
        Response::json(['message' => 'Not implemented'], Response::NOT_IMPLEMENTED);
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
        return $this->parameters[$key];
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