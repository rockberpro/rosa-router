<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Core\GetRequest;
use Rockberpro\RestRouter\Core\PostRequest;
use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\RequestData;
use Rockberpro\RestRouter\Core\RequestException;
use Rockberpro\RestRouter\Core\Response;
use Rockberpro\RestRouter\Core\Route;

/**
 * Records middleware / destination execution order across a single dispatch.
 */
class DispatchTrace
{
    public static array $calls = [];
}

class DispatchRecordingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        DispatchTrace::$calls[] = 'mw';
        return $next($request);
    }
}

class DispatchShortCircuitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        DispatchTrace::$calls[] = 'short';
        // deliberately never calls $next -> destination must not run
        return new Response(['blocked' => true], Response::FORBIDDEN);
    }
}

/**
 * Exercises the matching/dispatch core (URL param extraction, no-match and
 * method-mismatch behaviour, and the middleware pipeline actually executing),
 * complementing NestedRoutesTest which only asserts route-table assembly.
 *
 * Routing state lives in process-global statics; Route::reset() in setUp()
 * gives each test a clean registry.
 */
class DispatchTest extends TestCase
{
    protected function setUp(): void
    {
        Route::reset();
        DispatchTrace::$calls = [];
    }

    private function data(string $method, string $uri, array $queryParams = []): RequestData
    {
        return new RequestData($method, $uri, null, [], $queryParams);
    }

    public function testExtractsSinglePathParam(): void
    {
        Route::get('/user/{id}', fn() => new Response([], Response::OK));

        $request = (new GetRequest())->buildRequest($this->data('GET', '/api/user/42'));

        $this->assertSame('42', $request->getPathParam('id'));
    }

    public function testExtractsMultiplePathParams(): void
    {
        Route::get('/user/{id}/post/{postId}', fn() => new Response([], Response::OK));

        $request = (new GetRequest())->buildRequest($this->data('GET', '/api/user/7/post/abc'));

        $this->assertSame('7', $request->getPathParam('id'));
        $this->assertSame('abc', $request->getPathParam('postId'));
    }

    public function testTrailingSlashIsOptional(): void
    {
        Route::get('/user/{id}', fn() => new Response([], Response::OK));

        $request = (new GetRequest())->buildRequest($this->data('GET', '/api/user/42/'));

        $this->assertSame('42', $request->getPathParam('id'));
    }

    public function testQueryParamsArePopulated(): void
    {
        Route::get('/search', fn() => new Response([], Response::OK));

        $request = (new GetRequest())->buildRequest(
            $this->data('GET', '/api/search', ['q' => 'rosa', 'page' => '2'])
        );

        $this->assertSame('rosa', $request->getQueryParam('q'));
        $this->assertSame('2', $request->getQueryParam('page'));
    }

    public function testRejectsNonAlphanumericPathParam(): void
    {
        Route::get('/user/{id}', fn() => new Response([], Response::OK));

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Invalid or missing value for route parameter: id');

        (new GetRequest())->buildRequest($this->data('GET', '/api/user/a-b'));
    }

    public function testNoMatchingRouteThrows(): void
    {
        Route::get('/user/{id}', fn() => new Response([], Response::OK));

        $this->expectException(RequestException::class);

        (new GetRequest())->buildRequest($this->data('GET', '/api/nope'));
    }

    public function testSegmentCountMismatchDoesNotMatch(): void
    {
        Route::get('/user/{id}', fn() => new Response([], Response::OK));

        $this->expectException(RequestException::class);

        // extra trailing segment -> different number of segments, no match
        (new GetRequest())->buildRequest($this->data('GET', '/api/user/42/extra'));
    }

    public function testMethodWithNoRoutesThrows(): void
    {
        // only a GET route is registered; a POST request has no POST table
        Route::get('/user/{id}', fn() => new Response([], Response::OK));

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('No routes defined for the given method: POST');

        (new PostRequest())->buildRequest($this->data('POST', '/api/user/42'));
    }

    public function testHandleReturnsNotFoundForEmptyPath(): void
    {
        Route::get('/user/{id}', fn() => new Response([], Response::OK));

        // getPath() falls back to the (empty) URI -> early Not found response
        $response = (new Request())->handle($this->data('GET', ''));

        $this->assertSame(Response::NOT_FOUND, $response->status);
        $this->assertSame(['message' => 'Not found'], $response->data);
    }

    public function testMiddlewarePipelineRunsThenDestination(): void
    {
        Route::middleware([DispatchRecordingMiddleware::class])->group(function () {
            Route::get('/ping', function () {
                DispatchTrace::$calls[] = 'dest';
                return new Response(['pong' => true], Response::OK);
            });
        });

        $response = (new Request())->handle($this->data('GET', '/api/ping'));

        $this->assertSame(['mw', 'dest'], DispatchTrace::$calls);
        $this->assertSame(['pong' => true], $response->data);
        $this->assertSame(Response::OK, $response->status);
    }

    public function testMiddlewareShortCircuitSkipsDestination(): void
    {
        Route::middleware([DispatchShortCircuitMiddleware::class])->group(function () {
            Route::get('/ping', function () {
                DispatchTrace::$calls[] = 'dest';
                return new Response(['pong' => true], Response::OK);
            });
        });

        $response = (new Request())->handle($this->data('GET', '/api/ping'));

        // destination must never run
        $this->assertSame(['short'], DispatchTrace::$calls);
        $this->assertSame(Response::FORBIDDEN, $response->status);
        $this->assertSame(['blocked' => true], $response->data);
    }

    public function testRouteWithoutMiddlewareRunsDestinationDirectly(): void
    {
        Route::get('/ping', function () {
            DispatchTrace::$calls[] = 'dest';
            return new Response(['pong' => true], Response::OK);
        });

        $response = (new Request())->handle($this->data('GET', '/api/ping'));

        $this->assertSame(['dest'], DispatchTrace::$calls);
        $this->assertSame(['pong' => true], $response->data);
    }
}
