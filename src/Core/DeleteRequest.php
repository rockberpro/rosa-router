<?php

namespace Rockberpro\RosaRouter\Core;

class DeleteRequest extends AbstractRequest
{
    /**
     * Build the request for Delete method
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