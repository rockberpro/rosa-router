<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\RequestData;

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
     * @param RequestData $requestData
     * @return Request
     */
    public function buildRequest($routes, RequestData $requestData): Request
    {
        return parent::buildBodyRequest($routes, $requestData);
    }
}