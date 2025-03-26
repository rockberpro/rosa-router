<?php

namespace Rockberpro\RestRouter\Helpers;

use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\RequestData;

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
     * @param RequestData $requestData
     * @return Request
     */
    public function buildRequest($routes, RequestData $requestData): Request
    {
        return parent::buildBodyRequest($routes, $requestData);
    }
}