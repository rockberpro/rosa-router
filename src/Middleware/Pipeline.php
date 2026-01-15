<?php

namespace Rockberpro\RosaRouter\Middleware;

use Closure;
use Rockberpro\RosaRouter\Core\Request;
use Rockberpro\RosaRouter\Core\RequestException;
use Rockberpro\RosaRouter\Core\Response;

class Pipeline
{
    /**
     * @var array<string> middleware class names
     */
    private array $middlewares = [];

    /**
     * @var Closure final destination (controller)
     */
    private Closure $destination;

    /**
     * Add middleware to the pipeline
     * 
     * @param string|array $middelware
     * @return self
     */
    public function through($middelware): self
    {
        if (is_array($middelware)) {
            $this->middlewares = array_merge($this->middlewares, $middelware);
        }
        else {
            $this->middlewares[]  = $middelware;
        }

        return $this;
    }

    /**
     * 
     * Set the final destination (controller execution)
     * 
     * @param Closure $destination
     * @return self
     */
    public function then(Closure $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    /**
     * Execute the pipeline
     * 
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            $this->carry(),
            $this->prepareDestination()
        );

        return $pipeline($request);
    }

    /**
     * Prepare the final destination closure
     * 
     * @return Closure
     */
    protected function prepareDestination(): Closure
    {
        return function(Request $request) {
            return ($this->destination)($request);
        };
    }

    /**
     * Create the middleware carrying closure
     * 
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function(Closure $stack, string $middelware) {
            return function(Request $request) use ($stack, $middelware) {
                if(!class_exists($middelware)) {
                    throw new RequestException("Middleware not found: {$middelware}");
                }

                $instance = new $middelware();

                if (!method_exists($instance, 'handle')) {
                    throw new RequestException("Method 'handle' not implemented for middleware: {$middelware}");
                }

                // pass $stack as $next
                return $instance->handle($request, $stack);
            };
        };
    }
}