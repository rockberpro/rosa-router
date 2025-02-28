<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Interfaces\ResponseInterface;

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

    /**
     * Get request
     * 
     * @method json
     * @param mixed $data
     * @param int $code
     */
    public static function json($data, $code)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        exit(
            json_encode($data)
        );
    }
}