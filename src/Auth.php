<?php

namespace Rockberpro\RestRouter;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class Auth
{
    /**
     * Check if API-Key is valid
     * 
     * @param string $api_key
     * @param string $client_key
     * @return void
     */
    public static function check($api_key, $client_key): void
    {
        if (!$api_key) {
            Response::json(['message' => 'API-Key could not be loaded'], Response::FORBIDDEN);
        }

        if (!$client_key) {
            Response::json(['message' => 'API-Key was not provided'], Response::FORBIDDEN);
        }

        if (!hash_equals($api_key, $client_key)) {
            Response::json(['message' => 'Incorrect API-Key provided'], Response::FORBIDDEN);
        }
    }
}