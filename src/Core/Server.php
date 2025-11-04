<?php

namespace Rockberpro\RestRouter\Core;

use React\Http\Message\ServerRequest;
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

    private bool $isStateful = false;

    /**
     * Prefer calling Server::setInstance() or Server::init()
     * during bootstrap. This removes responsibility to register itself
     * into the DI container from this class.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    public function __construct() {}

    public function isApiEndpoint(): bool
    {
        return strpos(self::getInstance()->getHttpRequest()->getPathInfo(), '/api/') !== false;
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
        $this->isStateful = true;

        return $this;
    }

    public function isStateful(): bool
    {
        return $this->isStateful;
    }

    /**
     * Execute the server request handling.
     */
    public function dispatch()
    {
        $response = (new RequestHandler())->dispatch($this->isStateful());
         if ($response instanceof \Rockberpro\RestRouter\Core\Response) {
             // if the incoming HTTP method is OPTIONS, send only headers/status without a body
             if (Server::requestMethod() === 'OPTIONS') {
                 $response->writeHeaders($response->getHeadersForOptions());
                 $response->exit();
             }
             // if the incoming HTTP method is HEAD, send only headers/status without a body
             if (Server::requestMethod() === 'HEAD') {
                 $response->writeHeaders($response->getHeadersForHead());
                 $response->exit();
             }
            $response->response();
         }
         if ($response instanceof \React\Http\Message\Response) {
             return $response;
         }

        return null;
    }

    /**
     * Get the Server instance
     * 
     * @return Server
     */
    public static function getInstance(): Server
    {
        // Return a previously set instance first
        if (self::$instance !== null) {
            return self::$instance;
        }
        // If the container already has an instance, use it but DO NOT create
        // and register one here — keep responsibility in bootstrap code.
        if (Container::getInstance()->has(Server::class)) {
            $instance = Container::getInstance()->get(Server::class);
            // Cache local reference for subsequent fast access
            self::$instance = $instance;
            return $instance;
        }

        throw new \RuntimeException(
            'Server instance is not initialized. Call Server::init() or Server::setInstance($server) during application bootstrap.'
        );
    }

    /**
     * Explicitly set the Server instance (useful for tests/bootstrap).
     */
    public static function setInstance(Server $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Convenience factory to create a Server based on globals and cache it.
     * This does NOT register the instance into the DI container — do that
     * in bootstrap if needed.
     */
    public static function init(): Server
    {
        $instance = new self();
        $instance->httpRequest = HttpRequest::createFromGlobals();
        self::setInstance($instance);
        return $instance;
    }

    public static function query(): string
    {
        return ServerHelper::query();
    }

    public static function method(): string
    {
        return ServerHelper::method();
    }

    public static function key(): string
    {
        return ServerHelper::key();
    }

    public static function authorization(): string
    {
        return ServerHelper::authorization();
    }

    public static function userAgent(): string
    {
        return ServerHelper::userAgent();
    }

    public static function documentRoot(): string
    {
        return ServerHelper::documentRoot();
    }

    public static function serverName(): string
    {
        return ServerHelper::serverName();
    }

    public static function serverAddress(): string
    {
        return ServerHelper::serverAddress();
    }

    public static function remoteAddress(): string
    {
        return ServerHelper::remoteAddress();
    }

    public static function targetAddress(): string
    {
        return ServerHelper::targetAddress();
    }

    public static function requestMethod(): string
    {
        return ServerHelper::requestMethod();
    }

    public static function requestUri(): string
    {
        return ServerHelper::requestUri();
    }
}