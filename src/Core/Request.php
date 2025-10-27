<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\RequestInterface;
use Rockberpro\RestRouter\Core\DeleteRequest;
use Rockberpro\RestRouter\Core\GetRequest;
use Rockberpro\RestRouter\Core\PatchRequest;
use Rockberpro\RestRouter\Core\PostRequest;
use Rockberpro\RestRouter\Core\PutRequest;
use Rockberpro\RestRouter\Core\RequestAction;
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
    private array $pathParams = [];
    private array $queryParams = [];
    private array $bodyParams = [];
    private ?RequestLogger $requestLogger = null;

    /**
     * @method handle
     * @param string $method
     * @param string $uri
     * @param string $queryPath
     * @param array $body
     * @param array $queryParams
     */
    public function handle(RequestData $requestData): Response
    {
        $path = $this->getPath($requestData);
        if (!$path) {
            return new Response(['message' => 'Not found'], Response::NOT_FOUND);
        }
        $request = null;
        switch ($requestData->getMethod()) {
            case 'GET':
                $request = (new GetRequest())->buildRequest($requestData);
                break;
            case 'POST':
                $request = (new PostRequest())->buildRequest($requestData);
                break;
            case 'PUT':
                $request = (new PutRequest())->buildRequest($requestData);
                break;
            case 'PATCH':
                $request = (new PatchRequest())->buildRequest($requestData);
                break;
            case 'DELETE':
                $request = (new DeleteRequest())->buildRequest($requestData);
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
            $response = $closure($request); // response
            return $response;
        }

        $class = $request->getAction()->getClass();
        $method = $request->getAction()->getMethod();

        // call the controller
        $response = (new $class)->$method($request);
        return $response;
    }

    /**
     * Get the route path
     * 
     * @param RequestData $data
     * @return string|null
     */
    private function getPath(RequestData $data)
    {
        if ($data->getPathQuery()): return $data->getPathQuery(); endif;
        return $data->getUri();
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
    public function getParams(): array
    {
        return array_merge(
            ['body_params' => $this->getAllBodyParams()],
            ['path_params' => $this->getAllPathParams()],
            ['query_params' => $this->getAllQueryParams()],
            ['url_encoded_params' => $this->getAllUrlEncodedParams()]
        );
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
        $body_param = $this->getBodyParam($key);
        $path_param = $this->getPathParam($key);
        $query_param = $this->getQueryParam($key);
        $url_encoded_param = $this->getUrlEncodedParam($key);

        return $body_param
            ?? $path_param
            ?? $query_param
            ?? $url_encoded_param
            ?? null;
    }

    /**
     * @param string $key
     * @param string $param
     * @return void
     */
    public function setQueryParam(string $key, string $param): void
    {
        $this->queryParams[$key] = $param;
    }

    /**
     * @param string $key
     * @return void
     */
    public function getQueryParam(string $key): ?string
    {
        if (!array_key_exists($key, $this->queryParams ?? [])) {
            return null;
        }
        return $this->queryParams[$key];
    }

    /**
     * @return array
     */
    public function getAllQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param string $key
     * @param string $param
     * @return void
     */
    public function setPathParam(string $key, string $param): void
    {
        $this->pathParams[$key] = $param;
    }

    /**
     * @param string $key
     * @return void
     */
    public function getPathParam(string $key): ?string
    {
        if (!array_key_exists($key, $this->pathParams ?? [])) {
            return null;
        }
        return $this->pathParams[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getAllPathParams(): array
    {
        return $this->pathParams;
    }

    /**
     * @param string $key
     * @return void
     */
    public function getUrlEncodedParam(string $key): ?string
    {
        return Server::getInstance()->getHttpRequest()->request->get($key);
    }

    /**
     * @return array
     */
    public function getAllUrlEncodedParams(): array
    {
        return Server::getInstance()->getHttpRequest()->request->all();
    }

    /**
     * @param string $key
     * @return void
     */
    public function getBodyParam(string $key): ?string
    {
        $content = Server::getInstance()->getHttpRequest()->getContent();
        $body = json_decode($content, true);

        if (!array_key_exists($key, $body ?? [])) {
            return null;
        }
        return $body[$key] ?: null;
    }

    /**
     * @return array
     */
    public function getAllBodyParams(): array
    {
        $content = Server::getInstance()->getHttpRequest()->getContent();
        $body = json_decode($content, true);

        return $body ?? [];
    }
}