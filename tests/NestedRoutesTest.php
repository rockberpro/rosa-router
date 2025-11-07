<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Core\Route;

class NestedRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        require_once getRootDir()."/vendor/autoload.php";
        require_once getRootDir().'/tests/middleware/TestMiddleware.php';
        require_once getRootDir().'/tests/mock/api.php';
    }

    public function testNestedRoutes()
    {
        $get_routes = Route::getRoutes()['GET'];
        // basic count
        $this->assertCount(4, $get_routes);

        // expected entries
        $expected = [
            [
                'method' => 'GET',
                'prefix' => '/api/lvl1/hello',
                'route'  => '/api/lvl1/hello',
                'middleware' => TestMiddleware::class,
            ],
            [
                'method' => 'GET',
                'prefix' => '/api/lvl1/lvl2/status',
                'route'  => '/api/lvl1/lvl2/status',
                'middleware' => TestMiddleware::class,
            ],
            [
                'method' => 'GET',
                'prefix' => '/api/lvl1/test',
                'route'  => '/api/lvl1/test',
                'middleware' => TestMiddleware::class,
            ],
              [
                'method' => 'GET',
                'prefix' => '/api/outside',
                'route'  => '/api/outside',
            ],
        ];

        foreach ($expected as $i => $exp) {
            $this->assertArrayHasKey($i, $get_routes, "Missing route index $i");

            $route = $get_routes[$i];

            $this->assertSame($exp['method'], $route['method'], "Method mismatch at index $i");
            $this->assertSame($exp['prefix'], $route['prefix'], "Prefix mismatch at index $i");
            $this->assertSame($exp['route'], $route['route'], "Route mismatch at index $i");

            // middleware: only check if expected declares it
            if (array_key_exists('middleware', $exp)) {
                $this->assertArrayHasKey('middleware', $route, "Missing middleware at index $i");
                $this->assertSame($exp['middleware'], $route['middleware'], "Middleware mismatch at index $i");
            } else {
                $this->assertArrayNotHasKey('middleware', $route, "Unexpected middleware at index $i");
            }

            // target should be a Closure and callable
            $this->assertInstanceOf(\Closure::class, $route['target'], "Target is not a Closure at index $i");
            $this->assertTrue(is_callable($route['target']), "Target is not callable at index $i");
        }
    }
}

function getRootDir() {
    return __DIR__ . '/../';
}