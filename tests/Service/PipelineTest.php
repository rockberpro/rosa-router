<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RosaRouter\Service\Pipeline;
use Rockberpro\RosaRouter\Core\Request;
use Rockberpro\RosaRouter\Core\Response;
use Rockberpro\RosaRouter\Core\RequestException;

/**
 * Shared call recorder so middleware/destination can report execution order.
 */
class PipelineTrace
{
    public static array $calls = [];
}

class PipelineFirstMiddleware
{
    public function handle($request, $next): Response
    {
        PipelineTrace::$calls[] = 'first';
        return $next($request);
    }
}

class PipelineSecondMiddleware
{
    public function handle($request, $next): Response
    {
        PipelineTrace::$calls[] = 'second';
        return $next($request);
    }
}

class PipelineShortCircuitMiddleware
{
    public function handle($request, $next): Response
    {
        PipelineTrace::$calls[] = 'short';
        // deliberately does not call $next
        return new Response(['blocked' => true], Response::FORBIDDEN);
    }
}

class PipelineNoHandleMiddleware
{
    // intentionally has no handle() method
}

class PipelineTest extends TestCase
{
    protected function setUp(): void
    {
        PipelineTrace::$calls = [];
    }

    private function destination(): Closure
    {
        return function (Request $request): Response {
            PipelineTrace::$calls[] = 'destination';
            return new Response(['ok' => true], Response::OK);
        };
    }

    public function testMiddlewareRunsOuterToInnerThenDestination(): void
    {
        $response = (new Pipeline())
            ->through([PipelineFirstMiddleware::class, PipelineSecondMiddleware::class])
            ->then($this->destination())
            ->handle(new Request());

        $this->assertSame(['first', 'second', 'destination'], PipelineTrace::$calls);
        $this->assertSame(['ok' => true], $response->data);
        $this->assertSame(Response::OK, $response->status);
    }

    public function testRunsDestinationWhenNoMiddleware(): void
    {
        $response = (new Pipeline())
            ->then($this->destination())
            ->handle(new Request());

        $this->assertSame(['destination'], PipelineTrace::$calls);
        $this->assertSame(['ok' => true], $response->data);
    }

    public function testThroughAcceptsSingleStringMiddleware(): void
    {
        $response = (new Pipeline())
            ->through(PipelineFirstMiddleware::class)
            ->then($this->destination())
            ->handle(new Request());

        $this->assertSame(['first', 'destination'], PipelineTrace::$calls);
        $this->assertSame(Response::OK, $response->status);
    }

    public function testMiddlewareCanShortCircuitBeforeDestination(): void
    {
        $response = (new Pipeline())
            ->through([PipelineShortCircuitMiddleware::class, PipelineSecondMiddleware::class])
            ->then($this->destination())
            ->handle(new Request());

        // second middleware and destination must never run
        $this->assertSame(['short'], PipelineTrace::$calls);
        $this->assertSame(Response::FORBIDDEN, $response->status);
        $this->assertSame(['blocked' => true], $response->data);
    }

    public function testThrowsWhenMiddlewareClassDoesNotExist(): void
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Middleware not found');

        (new Pipeline())
            ->through('NoSuchMiddleware_' . uniqid())
            ->then($this->destination())
            ->handle(new Request());
    }

    public function testThrowsWhenMiddlewareHasNoHandleMethod(): void
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage("Method 'handle' not implemented");

        (new Pipeline())
            ->through(PipelineNoHandleMiddleware::class)
            ->then($this->destination())
            ->handle(new Request());
    }
}
