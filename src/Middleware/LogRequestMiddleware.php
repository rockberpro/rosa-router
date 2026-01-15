<?php

namespace Rockberpro\RosaRouter\Middleware;

use Rockberpro\RosaRouter\Core\Request;
use Rockberpro\RosaRouter\Core\Response;
use Rockberpro\RosaRouter\Logs\InfoLogHandler;
use Rockberpro\RosaRouter\Service\Container;
use Closure;

class LogRequestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->write($request);

        $response = $next($request);

        return $response;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function write(Request $request): void
    {
        $logger = Container::getInstance()->get(InfoLogHandler::class);
        $is_closure = $request->getAction()->isClosure();
        $log_data = [
            'type' => $is_closure ? 'closure' : 'controller',
            'request_data' => $request->getParams(),
            'endpoint' => $request->getAction()->getUri(),
        ];
        if (!$is_closure) {
            $log_data['class'] = $request->getAction()->getClass();
            $log_data['method'] = $request->getAction()->getMethod();
        }

        $logger->write('request', $log_data);
    }
}