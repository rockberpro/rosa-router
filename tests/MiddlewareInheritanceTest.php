<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Core\Route;

class OuterMiddleware
{
    public function handle() {}
}

class InnerMiddleware
{
    public function handle() {}
}

/**
 * Middleware declared on nested groups must accumulate (outer -> inner),
 * not override. This is what makes an outer "log everything" group actually
 * cover routes that also sit inside an inner auth group.
 *
 * Route state is held in process-global statics; Route::reset() in setUp()
 * gives each test a clean registry without spawning a separate process.
 */
class MiddlewareInheritanceTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        Route::reset();
        require __DIR__ . '/mock/middleware_merge_api.php';
    }

    private function routeByPath(string $path): array
    {
        foreach (Route::getRoutes()['GET'] as $route) {
            if ($route['route'] === $path) {
                return $route;
            }
        }
        $this->fail("Route not found: {$path}");
    }

    public function testOuterMiddlewareAppliesWhenNoInnerMiddleware(): void
    {
        $route = $this->routeByPath('/api/merge/plain');

        $this->assertSame([OuterMiddleware::class], $route['middleware']);
    }

    public function testInnerGroupInheritsOuterMiddlewareAndAddsItsOwn(): void
    {
        $route = $this->routeByPath('/api/merge/guarded');

        // outer-most first, then inner — order matters for wrapping
        $this->assertSame(
            [OuterMiddleware::class, InnerMiddleware::class],
            $route['middleware']
        );
    }

    public function testMiddlewareDeclaredAtMultipleLevelsIsDeduped(): void
    {
        $route = $this->routeByPath('/api/merge/dup');

        $this->assertSame(
            [OuterMiddleware::class, InnerMiddleware::class],
            $route['middleware']
        );
    }
}
