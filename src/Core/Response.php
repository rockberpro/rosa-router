<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\ResponseInterface;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
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

    public array $data;
    public int $status;

    public function __construct($data, $status)
    {
        $this->data = $data;
        $this->status = $status;
    }

    /**
     * Summary of response
     * 
     * @param array $data
     * @param int $status
     * @exit
     */
    public function response(): void
    {
        self::json($this->data, $this->status);
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
    public static function json($data, $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        exit(
            json_encode($data)
        );
    }
}