<?php

namespace Rockberpro\RosaRouter\Core;

use Rockberpro\RosaRouter\Service\Pipeline;

class Request implements RequestInterface
{
    private RequestAction $requestAction;
    private RequestData $requestData;
    private array $pathParams = [];
    private array $queryParams = [];

    /**
     * @method handle
     * @param RequestData $data
     */
    public function handle(RequestData $data): Response
    {
        if (!$this->getPath($data)) {
            return new Response(['message' => 'Not found'], Response::NOT_FOUND);
        }
        $request = null;
        $method = $data->getMethod();
        switch ($method) {
            case 'GET':
                $request = (new GetRequest())->buildRequest($data);
                break;
            case 'HEAD':
                $request = (new HeadRequest())->buildRequest($data);
                break;
            case 'OPTIONS':
                $request = (new OptionsRequest())->buildRequest($data);
                break;
            case 'POST':
                $request = (new PostRequest())->buildRequest($data);
                break;
            case 'PUT':
                $request = (new PutRequest())->buildRequest($data);
                break;
            case 'PATCH':
                $request = (new PatchRequest())->buildRequest($data);
                break;
            case 'DELETE':
                $request = (new DeleteRequest())->buildRequest($data);
                break;
            default: break;
        }

        if (!$request) {
            throw new RequestException('It was not possible to match your request');
        }

        $middleware = $request->getAction()->getMiddleware();
        if ($middleware) {
            $middlewares = is_array($middleware) ? $middleware : [$middleware];

            $pipeline = new Pipeline();
            $response = $pipeline
                ->through($middlewares)
                ->then(function(Request $req) use ($method) {
                    return $this->executeController($method, $req);
                })
                ->handle($request);

            return $response;
        }

        // no binded middleware
        return $this->executeController($method, $request);
    }

    private function executeController(string $method, Request $request): Response
    {
        $response = $this->getClosureResponse($method, $request);
        if ($response) {
            return $response;
        }

        $response = $this->getControllerResponse($request);
        if ($response) {
            return $response;
        }

        throw new RequestException('It was not possible to match your request');
    }

    /**
     * @param string $method
     * @param Request $request
     * @return Response
     */
    private function getClosureResponse(string $method, Request $request): ?Response
    {
        $closure = $request->getAction()->getClosure();
        if ($closure instanceof \Closure) {
            $response = $closure($request);
            $response->status = $this->getStatusCodeForMethod($method, $response);
            return $response;
        }
        return null;
    }

    /**
     * @param string $method
     * @param Response $response
     * @return int
     */
    private function getStatusCodeForMethod(string $method, Response $response): int
    {
        if ($method === 'HEAD') {
            return $response->status;
        }
        if ($method === 'OPTIONS') {
            return Response::NO_CONTENT;
        }
        return $response->status;
    }

    /**
     * @param Request $request
     * @return Response
     */
    private function getControllerResponse(Request $request): Response
    {
        $class = $request->getAction()->getClass();
        $method = $request->getAction()->getMethod();

        // call the controller
        $response = (new $class)->$method($request);
        if (!($response instanceof Response)) {
            throw new ResponseException('The controller method must return an instance of Response');
        }

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
     * Set the request action
     * 
     * @method setAction
     * @param RequestAction $action
     * @return void
     */
    public function setAction(RequestAction $action): void
    {
        $this->requestAction = $action;
    }

    /**
     * Get the request action
     * 
     * @method getAction
     * @return RequestAction
     */
    public function getAction(): RequestAction
    {
        return $this->requestAction;
    }

    /**
     * Set the request data
     *
     * @method setData
     * @param RequestData $data
     * @return void
     */
    public function setData(RequestData $data)
    {
        $this->requestData = $data;
    }

    /**
     * Get the request data
     *
     * @method getData
     * @return RequestData
     */
    public function getData(): RequestData
    {
        return $this->requestData;
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
        $content = $this->getAllBodyParams();
        if (!array_key_exists($key, $content)) {
            return null;
        }

        return $content[$key] ?: null;
    }

    /**
     * @return array
     */
    public function getAllBodyParams(): array
    {
        return Server::getInstance()->getRequestBody();
    }
}

final class RequestException extends \RuntimeException {}