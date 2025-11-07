<?php

namespace Rockberpro\RestRouter\Core;

class OptionsRequest extends AbstractRequest
{
    /**
     * Build the request for Get method
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