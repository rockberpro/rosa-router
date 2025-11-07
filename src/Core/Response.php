<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Utils\DotEnv;

class Response implements ResponseInterface
{
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NO_CONTENT = 204;

    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const SEE_OTHER = 303;
    const NOT_MODIFIED = 304;
    const TEMPORARY_REDIRECT = 307;

    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const NOT_ACCEPTABLE = 406;
    const REQUEST_TIMEOUT = 408;
    const CONFLICT = 409;
    const GONE = 410;
    const UNPROCESSABLE_ENTITY = 422;
    const TOO_MANY_REQUESTS = 429;

    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;

    private string $method;
    public array $data;
    public int $status;

    public function __construct($data = [], $status = 0)
    {
        $this->data = $data;
        $this->status = $status;
    }

    /**
     * Payload response
     * 
     * @param array $data
     * @param int $status
     * @exit
     */
    public function response(): void
    {
        self::json($this->data, $this->status);
    }

    public function exit(): void
    {
        exit;
    }

    /**
     * Returns headers appropriate for a HEAD response as an associative array
     * Keys are header names and values are header values.
     * Adjust or extend these headers if you need different behavior (e.g. include Content-Length).
     *
     * @return array<string,string>
     */
    public function getHeadersForHead(): array
    {
        return [
            'Content-Type' => 'application/json; charset=utf-8',
            // indicate empty body (can be adjusted to actual length if known)
            'Content-Length' => '0',
        ];
    }

    /**
     * Returns headers appropriate for an OPTIONS (preflight) response as an associative array.
     * These include Allow and common CORS headers. You can compute Allow dynamically if needed.
     *
     * @return array<string,string>
     */
    public function getHeadersForOptions(): array
    {
        $origin = DotEnv::get('API_ALLOW_ORIGIN');
        return [
            'Content-Type' => 'application/json; charset=utf-8',
            'Allow' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD',
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
            'Content-Length' => '0',
        ];
    }

    /**
     * Insert headers into the response using native PHP header() and then send status via head().
     * This method does not return: it calls head() which exits.
     *
     * @param array<string,string> $headers
     * @return void
     */
    public function writeHeaders(array $headers): void
    {
        http_response_code($this->status);
        foreach ($headers as $name => $value) {
            // use header() to add the header; replace set to true to avoid duplicates by default
            header(sprintf('%s: %s', $name, $value), true);
        }
    }

    /**
     * Response
     * 
     * * end of execution
     * 
     * @method json
     * @param array $data
     * @param int $status
     * @exit
     */
    public static function json($data, $status)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        exit(
            json_encode($data)
        );
    }
}

final class ResponseException extends \RuntimeException {}