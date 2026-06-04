<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Helpers\RouteHelper;

class RouteHelperTest extends TestCase
{
    public function testRouteVarsSplitsLiteralsAndPlaceholders(): void
    {
        $this->assertSame(
            ['/users/', '{id}', '/posts/', '{pid}'],
            RouteHelper::routeVars('/users/{id}/posts/{pid}')
        );
    }

    public function testRouteVarsWithNoPlaceholders(): void
    {
        $this->assertSame(['/users/list'], RouteHelper::routeVars('/users/list'));
    }

    public function testRouteMatchArgsMatchesRouteVars(): void
    {
        $route = '/api/{version}/users/{id}';

        $this->assertSame(
            RouteHelper::routeVars($route),
            RouteHelper::routeMatchArgs($route)
        );
    }

    public function testRouteArgsSplitsLeadingSegmentAndPlaceholder(): void
    {
        $this->assertSame(
            ['/users/', '{id}'],
            RouteHelper::routeArgs(['/users/{id}'])
        );
    }

    public function testIsAlphaNumericAcceptsWordCharacters(): void
    {
        $this->assertSame(1, RouteHelper::isAlphaNumeric('abc_123'));
    }

    public function testIsAlphaNumericRejectsSeparators(): void
    {
        $this->assertSame(0, RouteHelper::isAlphaNumeric('abc-123'));
        $this->assertSame(0, RouteHelper::isAlphaNumeric('a/b'));
    }
}
