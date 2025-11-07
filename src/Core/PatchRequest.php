<?php

namespace Rockberpro\RestRouter\Core;

class PatchRequest extends AbstractRequest
{
    /**
     * Build the request for Patch method
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