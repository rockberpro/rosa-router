<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RosaRouter\Core\Request;
use Rockberpro\RosaRouter\Core\Response;

class TestMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/TestMiddleware.php';
    }

    public function testForwardsRequestToNextUnchanged(): void
    {
        $request = new Request();
        $seen = null;

        $next = function (Request $r) use (&$seen): Response {
            $seen = $r;
            return new Response(['ok' => true], Response::OK);
        };

        $response = (new TestMiddleware())->handle($request, $next);

        // the exact request instance must be passed through untouched
        $this->assertSame($request, $seen);
        // and the next handler's response is returned as-is
        $this->assertSame(['ok' => true], $response->data);
        $this->assertSame(Response::OK, $response->status);
    }
}
