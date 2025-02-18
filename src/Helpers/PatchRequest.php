<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Request;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers
 */
class PatchRequest extends AbstractRequest 
{
    /**
     * Build the request for Patch method
     * 
     * @method buildRequest
     * @param array $routes
     * @param string $method
     * @param string $uri
     * @param array $form
     * @return Request
     */
    public function buildRequest($routes, $method, $uri, $form) : Request
    {
        return parent::buildFormRequest($routes, $method, $uri, $form);
    }
}