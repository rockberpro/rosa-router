<?php

use Rockberpro\RosaRouter\Core\Request;
use Rockberpro\RosaRouter\Core\Response;
use Rockberpro\RosaRouter\Middleware\MiddlewareInterface;

/**
 * Test fixture: a transparent pass-through middleware.
 *
 * It implements the real MiddlewareInterface so the fixture stays honest if
 * the contract changes, and simply forwards the request to the next handler
 * without altering it.
 */
class TestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        return $next($request);
    }
}
