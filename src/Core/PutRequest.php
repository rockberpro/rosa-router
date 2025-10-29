<?php

namespace Rockberpro\RestRouter\Core;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Core
 */
class PutRequest extends AbstractRequest 
{
    /**
     * Build the request for Put method
     * 
     * @method buildRequest
     * @param RequestData $data
     * @return Request
     */
    public function buildRequest(RequestData $data): Request
    {
        return parent::buildRequest($data);
    }
}