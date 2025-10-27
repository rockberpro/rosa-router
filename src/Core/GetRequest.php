<?php

namespace Rockberpro\RestRouter\Core;

use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\RequestData;

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
     * @param RequestData $requestData
     * @return Request
     */
    public function buildRequest(RequestData $requestData): Request
    {
        return parent::buildRequest($requestData);
    }
}