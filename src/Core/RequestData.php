<?php

namespace Rockberpro\RosaRouter\Core;

class RequestData
{
    private string $method;
    private string $uri;
    private ?string $pathQuery;
    private ?array $body;
    private ?array $queryParams;

    public function __construct(
        string $method,
        string $uri,
        ?string $pathQuery = null,
        ?array $body = null,
        ?array $queryParams = null
    ) {
        $this->setMethod($method);
        $this->setUri($uri);
        $this->setPathQuery($pathQuery);
        $this->setBody($body);
        $this->setQueryParams($queryParams);
    }

    public function getMethod(): string
    {
        return $this->method;
    }
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }
    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getPathQuery(): ?string
    {
        return $this->pathQuery;
    }
    public function setPathQuery(?string $pathQuery): void
    {
        $this->pathQuery = $pathQuery;
    }

    public function getBody(): ?array
    {
        return $this->body;
    }
    public function setBody(?array $body): void
    {
        $this->body = $body;
    }

    public function getQueryParams(): ?array
    {
        return $this->queryParams;
    }
    public function setQueryParams(?array $queryParams): void
    {
        $this->queryParams = $queryParams;
    }
}