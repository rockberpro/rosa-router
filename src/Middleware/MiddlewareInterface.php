<?php

namespace Rockberpro\RosaRouter\Middleware;

use Rockberpro\RosaRouter\Core\Request;
use Rockberpro\RosaRouter\Core\Response;
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