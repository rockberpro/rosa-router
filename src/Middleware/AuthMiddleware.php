<?php

namespace Rockberpro\RestRouter\Middleware;

use Rockberpro\RestRouter\Core\Response;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Utils\Cors;
use Rockberpro\RestRouter\Utils\Sop;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Jwt;
use Rockberpro\RestRouter\Database\Handlers\PDOApiKeysHandler;

class AuthMiddleware
{
    /**
     * Secure the request
     * 
     * @method handle
     * @return void
     */
    public function handle(): void
    {
        Sop::check();

        if (!DotEnv::get('API_AUTH_METHOD')) {
            Response::json(['message' => "Access denied"], Response::UNAUTHORIZED);
        }

        if (DotEnv::get('API_AUTH_METHOD') === 'JWT') {
            Jwt::validate(Server::authorization(), 'access');
        }

        if (DotEnv::get('API_AUTH_METHOD') === 'KEY') {
            $apiKey = new PDOApiKeysHandler();
            if (!$apiKey->exists(Server::key())) {
                Response::json(['message' => "Access denied"], Response::UNAUTHORIZED);
            }
            if ($apiKey->isRevoked(Server::key())) {
                Response::json(['message' => "Access denied"], Response::UNAUTHORIZED);
            }
        }

        Cors::allowOrigin();
    }
}