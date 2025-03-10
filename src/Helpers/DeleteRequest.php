<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Request;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
class DeleteRequest extends AbstractRequest 
{
    /**
     * Build the request for Delete method
     * 
     * @method buildRequest
     * @param array $routes
     * @param string $method
     * @param string $uri
     * @return Request
     */
    public function buildRequest($routes, $method, $uri): Request
    {
        return parent::buildUriRequest($routes, $method, $uri);
    }
}