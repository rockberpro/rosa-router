<?php

namespace Rockberpro\RosaRouter\Core;

use React\Http\Message\ServerRequest;
use Rockberpro\RosaRouter\Bootstrap;
use Rockberpro\RosaRouter\Service\Container;
use Rockberpro\RosaRouter\Service\ContainerInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

final class Server implements ServerInterface
{
    public const MODE_STATELESS = 'stateless';
    public const MODE_STATEFUL = 'stateful';

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
     * Usage: $req = \Rockberpro\RosaRouter\Core\Server::getHttpRequest();
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
        $response = (new RequestHandler())->dispatch();
         if ($response instanceof \Rockberpro\RosaRouter\Core\Response) {
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
     * Set a custom container implementation.
     */
    public function setContainer(ContainerInterface $container): self
    {
        Container::setInstance($container);
        return $this;
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

    /**
     * Load route definitions from a PHP file.
     *
     * This helper includes the provided file in an
     * isolated scope so route definitions don't leak variables.
     *
     * @param string $file Absolute or relative path to the routes file
     * @return void
     */
    public function loadRoutes(string $file): void
    {
        // Include the routes file inside a closure to avoid leaking
        // local variables into the global scope used by the routes file.
        $loader = static function (string $path) {
            if (!file_exists($path)) {
                throw new \InvalidArgumentException("Routes file not found: {$path}");
            }
            include $path;
        };

        // Normalize relative paths: if the provided path doesn't exist,
        // try resolving it relative to the project root (two levels up).
        $path = $file;
        if (!file_exists($path)) {
            $alt = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . ltrim($file, '/\\');
            if (file_exists($alt)) {
                $path = $alt;
            }
        }

        $loader($path);
    }

    /**
     * @param string|null $mode one of Boostrap::MODE_STATELESS|Boostrap::MODE_STATEFUL or null to autodetect
     * @return void
     */
    /**
     * Centralized executor. Accepts an explicit mode or will detect one when null.
     * - If mode is stateless: dispatch immediately and return the dispatch result (or null when not an API endpoint).
     * - If mode is stateful: return a callable compatible with React\Http\HttpServer (same as previous stateful()).
     * This preserves backward compatibility while providing a single authoritative entry point.
     *
     * @param string|null $mode one of self::MODE_STATELESS|self::MODE_STATEFUL or null to autodetect
     * @return mixed|null|callable
     */
    public static function execute(?string $mode = null)
    {
        if (!Bootstrap::isBooted()) {
            Bootstrap::setup();
        }

        if ($mode === null) {
            $mode = self::detectMode();
        }

        switch ($mode) {
            case self::MODE_STATELESS:
                return self::doStateless();

            case self::MODE_STATEFUL:
                return self::doStateful();

            default:
                throw new \InvalidArgumentException("Unknown bootstrap mode: {$mode}");
        }
    }

    /**
     * Internal implementation for stateless handling (preserves previous behavior).
     */
    protected static function doStateless()
    {
        if (self::$instance === null && !Container::getInstance()->has(Server::class)) {
            $server = Server::init();
            Container::getInstance()->set(Server::class, $server);
        }

        return Server::getInstance()->dispatch();
    }

    /**
     * Internal implementation for stateful handling (preserves previous behavior).
     * Returns the callable expected by React\Http\HttpServer.
     */
    protected static function doStateful(): callable
    {
        return function (ServerRequest $request) {
            return Server::getInstance()
                ->stateful($request)
                ->dispatch();
        };
    }

    /**
     * Simple detection heuristics for the API mode
     */
    protected static function detectMode(): string
    {
        // CLI should default to stateless (no HTTP sessions)
        if (php_sapi_name() === 'cli' || PHP_SAPI === 'cli') {
            return self::MODE_STATEFUL;
        }

        // default/fallback
        return self::MODE_STATELESS;
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