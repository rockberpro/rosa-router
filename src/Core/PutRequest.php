<?php

namespace Rockberpro\RosaRouter\Core;

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