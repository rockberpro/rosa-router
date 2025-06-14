<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\RequestInterface;
use Rockberpro\RestRouter\Core\DeleteRequest;
use Rockberpro\RestRouter\Core\GetRequest;
use Rockberpro\RestRouter\Core\PatchRequest;
use Rockberpro\RestRouter\Core\PostRequest;
use Rockberpro\RestRouter\Core\PutRequest;
use Rockberpro\RestRouter\Core\RequestAction;
use Rockberpro\RestRouter\Utils\UrlParser;
use Rockberpro\RestRouter\Utils\Json;
use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Logs\RequestLogger;
use RuntimeException;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class Request implements RequestInterface
{
    private RequestAction $action;
    private array $parameters = [];
    private ?ErrorLogHandler $errorLogHander = null;
    private ?InfoLogHandler $infoLogHandler = null;
    private ?RequestLogger $requestLogger = null;

    /**
     * Get the body data
     * 
     * @method body
     * @param bool $parse
     * @return array|bool|string|null
     */
    public static function body($parse = true)
    {
        $input = file_get_contents("php://input");

        if (Json::isJson($input)) {
            return (array) json_decode($input, true);
        }
        if (!$parse) {
            return $input;
        }

        $data = [];
        parse_str($input, $data);
        if (empty($data)) {
            return null;
        }

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
    public function handle(RequestData $requestData)
    {
        global $routes;
        if ($routes === null) {
            return new Response(['message' => 'No registered routes'], Response::NOT_FOUND);
        }

        $path = $this->getPath($requestData);
        if (!$path) {
            return new Response(['message' => 'Not found'], Response::NOT_FOUND);
        }
        $requestData->setUri($path);

        $request = null;
        switch ($requestData->getMethod()) {
            case 'GET':
                $request = (new GetRequest())->buildRequest($routes, $requestData);
                break;
            case 'POST':
                $request = (new PostRequest())->buildRequest($routes, $requestData);
                break;
            case 'PUT':
                $request = (new PutRequest())->buildRequest($routes, $requestData);
                break;
            case 'PATCH':
                $request = (new PatchRequest())->buildRequest($routes, $requestData);
                break;
            case 'DELETE':
                $request = (new DeleteRequest())->buildRequest($routes, $requestData);
                break;
            default: break;
        }

        if ($request === null) {
            throw new RuntimeException('It was not possible to match your request');
        }

        if ($this->getRequestLogger()) {
            $this->getRequestLogger()->writeLog($request);
        }

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
     * Get the route path
     * 
     * @param RequestData $requestData
     * @return string|null
     */
    private function getPath(RequestData $requestData)
    {
        if ($requestData->getPathQuery()) {
            $path = UrlParser::path($requestData->getPathQuery());
            return $path;
        }

        $path = UrlParser::path($requestData->getUri());

        $segments = explode('/', $path ?? '');
        array_shift($segments);
        if ($segments[0] !== 'api') {
            return null;
        }

        return $path;
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

    public function setErrorLogger(?ErrorLogHandler $logger) {
        $this->errorLogHander = $logger;

        return $this;
    }
    public function getErrorLogger(): ?ErrorLogHandler {
        return $this->errorLogHander;
    }

    public function setInfoLogger(?InfoLogHandler $logger) {
        $this->infoLogHandler = $logger;
        $this->requestLogger = new RequestLogger(
            $this->getInfoLogger()
        );
        return $this;
    }
    public function getInfoLogger(): ?InfoLogHandler {
        return $this->infoLogHandler;
    }

    public function setRequestLogger(?RequestLogger $logger)
    {
        $this->requestLogger = $logger;
        return $this;
    }
    public function getRequestLogger(): ?RequestLogger
    {
        return $this->requestLogger;
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