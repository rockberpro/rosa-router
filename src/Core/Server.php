<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\ServerInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Rockberpro\RestRouter\Core\Route;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class Server implements ServerInterface
{
    /**
     * Cached HttpFoundation Request instance (singleton)
     *
     * @var HttpRequest|null
     */
    private ?HttpRequest $httpRequest = null;

    private static Server $instance;

    public function __construct() {
        self::$instance = $this;
        $this->httpRequest = HttpRequest::createFromGlobals();
        $this->httpRequest->attributes->set('routes', Route::getRoutes());
    }

    public function isApiEndpoint(): bool
    {
        return strpos($this->httpRequest->getPathInfo(), '/api/') !== false;
    }

    private function setRoutes(array $routes): void
    {
        $this->httpRequest->attributes->set('routes', $routes);
    }

    public function getRoutes(): array
    {
        return $this->httpRequest->attributes->get('routes', []);
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

    public static function routeArgv(): string
    {
        if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            return explode('path=', $_SERVER['argv'][0])[1] ?? '';
        }
        return '';
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
        $httpRequest = self::getHttpRequest();
        return new RequestData(
            $httpRequest->getMethod(),
            $httpRequest->getPathInfo(),
            $httpRequest->getQueryString(),
            json_decode($httpRequest->getContent(), true),
            $httpRequest->query->all()
        );
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

    /**
     * Get the Server instance
     * 
     * @return Server
     */
    public static function getInstance(): Server
    {
        return self::$instance;
    }
}