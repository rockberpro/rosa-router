<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Request;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
class PutRequest extends AbstractRequest 
{
    /**
     * Build the request for Put method
     * 
     * @method buildRequest
     * @param array $routes
     * @param string $method
     * @param string $uri
     * @param array $body
     * @return Request
     */
    public function buildRequest($routes, $method, $uri, $body) : Request
    {
        return parent::buildFormRequest($routes, $method, $uri, $body);
    }
}