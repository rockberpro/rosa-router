<?php

namespace Rockberpro\RestRouter\Middleware;

use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\Response;
use Closure;

interface MiddlewareInterface
{
    /**
     * Handle the rquest and pass to next middleware
     * 
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}