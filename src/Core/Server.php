<?php

namespace Rockberpro\RosaRouter\Core;

use React\Http\Message\ServerRequest;
use Rockberpro\RosaRouter\Bootstrap;
use Rockberpro\RosaRouter\Core\Transport\StatefulTransport;
use Rockberpro\RosaRouter\Core\Transport\StatelessTransport;
use Rockberpro\RosaRouter\Core\Transport\Transport;
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

    /**
     * The active transport for the current request. Chosen once by execute()
     * and used for reading the request and emitting the response.
     *
     * @var Transport|null
     */
    private ?Transport $transport = null;

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
        return $this->transport->requestData();
    }

    public function getRequestBody(): array
    {
        return $this->transport->requestBody();
    }

    public function getUrlEncodedParams(): array
    {
        return $this->transport->urlEncodedParams();
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

    /**
     * Execute the server request handling: run the transport-agnostic pipeline,
     * then let the active transport emit the resulting response.
     */
    public function dispatch()
    {
        $response = (new RequestHandler())->dispatch();

        return $this->transport->emit($response);
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
                if (self::$instance === null && !Container::getInstance()->has(Server::class)) {
                    Container::getInstance()->set(Server::class, Server::init());
                }
                $server = self::getInstance();
                $server->transport = new StatelessTransport($server->getHttpRequest());

                return $server->dispatch();

            case self::MODE_STATEFUL:
                // Return the callable expected by React\Http\HttpServer. The
                // transport is (re)bound per incoming request.
                return function (ServerRequest $request) {
                    $server = self::getInstance();
                    $server->transport = new StatefulTransport($request);

                    return $server->dispatch();
                };

            default:
                throw new \InvalidArgumentException("Unknown bootstrap mode: {$mode}");
        }
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

    public static function origin(): string
    {
        return ServerHelper::origin();
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