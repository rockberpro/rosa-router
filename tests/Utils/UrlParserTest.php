<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Utils\UrlParser;

class UrlParserTest extends TestCase
{
    public function testPathQueryExtractsPathValue(): void
    {
        $this->assertSame('/api/foo', UrlParser::pathQuery('path=/api/foo&x=1'));
    }

    public function testPathQueryReturnsEmptyStringWhenNoPathKey(): void
    {
        $this->assertSame('', UrlParser::pathQuery('x=1&y=2'));
        $this->assertSame('', UrlParser::pathQuery(''));
    }

    public function testQueryReturnsAssociativeArray(): void
    {
        $this->assertSame(
            ['x' => '1', 'y' => '2'],
            UrlParser::query('/api/foo?x=1&y=2')
        );
    }

    public function testQueryReturnsEmptyArrayWhenNoQueryString(): void
    {
        $this->assertSame([], UrlParser::query('/api/foo'));
    }

    public function testPathExtractsPathPortion(): void
    {
        $this->assertSame('/users/1', UrlParser::path('/users/1?x=1'));
    }

    public function testPathStripsTrailingQueryString(): void
    {
        $this->assertSame('/api/v1/users', UrlParser::path('/api/v1/users?page=2&limit=10'));
    }
}
