<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\RequestData;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Core
 */
class GetRequest extends AbstractRequest 
{
    /**
     * Build the request for Get method
     * 
     * @method buildRequest
     * @param array $routes
     * @param RequestData $requestData
     * @return Request
     */
    public function buildRequest($routes, RequestData $requestData): Request
    {
        return parent::buildUriRequest($routes, $requestData);
    }
}