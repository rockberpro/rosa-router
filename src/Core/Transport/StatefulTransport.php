<?php

namespace Rockberpro\RosaRouter\Core\Transport;

use React\Http\Message\Response as ReactResponse;
use React\Http\Message\ServerRequest;
use Rockberpro\RosaRouter\Core\RequestData;
use Rockberpro\RosaRouter\Core\Response;

/**
 * Stateful transport: reads the request from a per-request ReactPHP
 * ServerRequest and emits a React\Http\Message\Response.
 */
final class StatefulTransport implements Transport
{
    public function __construct(private ServerRequest $request) {}

    public function requestData(): RequestData
    {
        $request = $this->request;

        return new RequestData(
            $request->getMethod(),
            $request->getUri()->getPath(),
            $request->getUri()->getQuery(),
            $this->requestBody(),
            $request->getQueryParams()
        );
    }

    public function requestBody(): array
    {
        $parsedBody = $this->request->getParsedBody();

        return is_array($parsedBody) ? $parsedBody : [];
    }

    public function urlEncodedParams(): array
    {
        // ReactPHP parses form-urlencoded bodies into the parsed body; there is
        // no separate url-encoded parameter bag as in the SAPI (globals) case.
        return $this->requestBody();
    }

    public function emit(Response $response): ReactResponse
    {
        $method = $this->request->getMethod();

        // for HEAD requests, return only headers (no body)
        if ($method === 'HEAD') {
            return new ReactResponse(
                $response->status,
                $response->getHeadersForHead(),
                ''
            );
        }

        // for OPTIONS requests, return only headers (no body)
        if ($method === 'OPTIONS') {
            return new ReactResponse(
                Response::NO_CONTENT,
                $response->getHeadersForOptions(),
                ''
            );
        }

        return new ReactResponse(
            $response->status,
            ['Content-Type' => 'application/json'],
            json_encode($response->data)
        );
    }
}
