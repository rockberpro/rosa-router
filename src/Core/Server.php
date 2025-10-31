<?php

namespace Rockberpro\RestRouter\Core;

use React\Http\Message\ServerRequest;
use Rockberpro\RestRouter\RequestHandler;
use Rockberpro\RestRouter\Service\Container;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
final class Server implements ServerInterface
{
    /**
     * Cached HttpFoundation Request instance (singleton)
     *
     * @var HttpRequest|null
     */
    private HttpRequest $httpRequest;

    private ServerRequest $serverRequest;

    private array $routes = [];

    public function __construct() {}

    public function isApiEndpoint(): bool
    {
        return strpos(self::getInstance()->getHttpRequest()->getPathInfo(), '/api/') !== false;
    }

    public function setRoutes(array $routes): void
    {
        self::getInstance()->routes = $routes;
    }

    public function getRoutes(): array
    {
        return self::getInstance()->routes;
    }

    public static function query(): string
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    public static function key(): string
    {
        return $_SERVER['HTTP_X_API_KEY'] ?? '';
    }

    public static function authorization(): string
    {
        return $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    }

    public static function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public static function documentRoot(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] ?? '';
    }

    public static function serverName(): string
    {
        return $_SERVER['SERVER_NAME'] ?? '';
    }

    public static function serverAddress(): string
    {
        return $_SERVER['SERVER_ADDR'] ?? '';
    }

    public static function remoteAddress(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public static function targetAddress(): string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    public static function requestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    public static function requestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    public static function getRootDir()
    {
        return dirname(__DIR__, 1);
    }

    /**
     * Get the request data
     * 
     * @return RequestData
     */
    public function getRequestData(): RequestData
    {
        if ($this->isStateful()) {
            $serverRequest = $this->serverRequest;
            return new RequestData(
                $serverRequest->getMethod(),
                $serverRequest->getUri()->getPath(),
                $serverRequest->getUri()->getQuery(),
                $serverRequest->getParsedBody() ?? [],
                $serverRequest->getQueryParams()
            );
        }

        $httpRequest = self::getHttpRequest();
        return new RequestData(
            $httpRequest->getMethod(),
            $httpRequest->getPathInfo(),
            $httpRequest->getQueryString(),
            $this->getRequestBody(),
            $httpRequest->query->all()
        );
    }

    public function getRequestBody(): array
    {
        if ($this->isStateful()) {
            $serverRequest = $this->serverRequest;
            $parsedBody = $serverRequest->getParsedBody();
            if (is_array($parsedBody)) {
                return $parsedBody;
            }
            return [];
        }

        $httpRequest = self::getHttpRequest();
        return $this->extractRequestBody($httpRequest);
    }

    /**
     * Extract request body as array supporting JSON and form-encoded bodies.
     *
     * @param HttpRequest $httpRequest
     * @return array
     */
    private function extractRequestBody(HttpRequest $httpRequest): array
    {
        $raw = $httpRequest->getContent();

        // If no raw content, prefer parsed parameters (e.g. $_POST)
        if ($raw === null || $raw === '') {
            return $httpRequest->request->all() ?: [];
        }

        $contentType = strtolower((string) $httpRequest->headers->get('content-type', ''));

        // JSON preferred parsing
        if (strpos($contentType, 'application/json') !== false) {
            try {
                return $httpRequest->toArray();
            } catch (\Throwable $e) {
                // fallthrough to other parsers
            }
        }

        // form data (application/x-www-form-urlencoded or multipart/form-data)
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false
            || strpos($contentType, 'multipart/form-data') !== false) {
            return $httpRequest->request->all() ?: [];
        }

        // fallback: try parse_str (handles "nomeCompleto=Samuel")
        $parsed = [];
        parse_str($raw, $parsed);
        if (!empty($parsed)) {
            return $parsed;
        }

        // last resort: try JSON decode
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [];
    }

    /**
     * Return a singleton Symfony HttpFoundation Request created from globals.
     *
     * Usage: $req = \Rockberpro\RestRouter\Core\Server::getHttpRequest();
     *
     * @return HttpRequest
     */
    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }

    public function stateful(ServerRequest $serverRequest)
    {
        $this->serverRequest = $serverRequest;

        return $this;
    }

    public function isStateful(): bool
    {
        return isset($this->serverRequest);
    }

    /**
     * Execute the server request handling.
     */
    public function dispatch()
    {
        $response = (new RequestHandler())->dispatch();
        if ($response instanceof \Rockberpro\RestRouter\Core\Response) {
            $response->response();
        }
        if ($response instanceof \React\Http\Message\Response) {
            return $response;
        }
    }

    /**
     * Get the Server instance
     * 
     * @return Server
     */
    public static function getInstance(): Server
    {
        if (!Container::getInstance()->has(Server::class)) {
            $instance = new self();
            $instance->httpRequest = HttpRequest::createFromGlobals();
            Container::getInstance()->set(Server::class, $instance);
        }

        return Container::getInstance()->get(Server::class);
    }
}