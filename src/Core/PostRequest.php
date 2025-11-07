<?php

namespace Rockberpro\RestRouter\Core;

class PostRequest extends AbstractRequest
{
    /**
     * Build the request for Post method
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