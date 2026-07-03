<?php

namespace Rockberpro\RosaRouter\Core\Transport;

use Rockberpro\RosaRouter\Core\RequestData;
use Rockberpro\RosaRouter\Core\Response;
use Rockberpro\RosaRouter\Core\Server;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Stateless transport: reads the request from PHP globals (Symfony
 * HttpFoundation) and emits the response by writing to the SAPI and exiting.
 */
final class StatelessTransport implements Transport
{
    public function __construct(private HttpRequest $httpRequest) {}

    public function requestData(): RequestData
    {
        $request = $this->httpRequest;

        return new RequestData(
            $request->getMethod(),
            $request->getPathInfo(),
            $request->getQueryString(),
            $this->requestBody(),
            $request->query->all()
        );
    }

    public function requestBody(): array
    {
        return $this->extractRequestBody($this->httpRequest);
    }

    public function urlEncodedParams(): array
    {
        return $this->httpRequest->request->all();
    }

    public function emit(Response $response)
    {
        // OPTIONS/HEAD send only headers/status without a body.
        if (Server::requestMethod() === 'OPTIONS') {
            $response->writeHeaders($response->getHeadersForOptions());
            $response->exit();
        }
        if (Server::requestMethod() === 'HEAD') {
            $response->writeHeaders($response->getHeadersForHead());
            $response->exit();
        }

        $response->response();
    }

    /**
     * Extract request body as array supporting JSON and form-encoded bodies.
     *
     * @return array<string,mixed>
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
}
