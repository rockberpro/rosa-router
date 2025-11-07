<?php

namespace Rockberpro\RosaRouter\Core;

interface RequestDataInterface
{
    public function getMethod(): string;
    public function setMethod(string $method): void;

    public function getUri(): string;
    public function setUri(string $uri): void;

    public function getPathQuery(): ?string;
    public function setPathQuery(?string $pathQuery): void;

    public function getBody(): ?array;
    public function setBody(?array $body): void;

    public function getQueryParams(): ?array;
    public function setQueryParams(?array $queryParams): void;
}