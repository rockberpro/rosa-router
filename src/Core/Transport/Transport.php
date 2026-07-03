<?php

namespace Rockberpro\RosaRouter\Core\Transport;

use Rockberpro\RosaRouter\Core\RequestData;
use Rockberpro\RosaRouter\Core\Response;

/**
 * A Transport encapsulates everything that differs between the stateless
 * (PHP globals / SAPI) and stateful (ReactPHP) execution modes:
 *
 *  - where the incoming request is read from, and
 *  - how the outgoing response is emitted.
 *
 * The routing/middleware/controller pipeline in between is transport-agnostic,
 * so the mode is decided exactly once (when the Transport is chosen) instead of
 * being re-checked at every layer.
 */
interface Transport
{
    /**
     * Build the normalized request data for the current request.
     */
    public function requestData(): RequestData;

    /**
     * The parsed request body (used by Request::getAllBodyParams()).
     *
     * @return array<string,mixed>
     */
    public function requestBody(): array;

    /**
     * The url-encoded form parameters for the current request
     * (used by Request::getAllUrlEncodedParams()).
     *
     * @return array<string,mixed>
     */
    public function urlEncodedParams(): array;

    /**
     * Emit the pipeline's Response in the shape this transport requires.
     *
     * Stateless transports write to the SAPI and exit (returns void);
     * stateful transports return a React\Http\Message\Response.
     *
     * @return mixed
     */
    public function emit(Response $response);
}
