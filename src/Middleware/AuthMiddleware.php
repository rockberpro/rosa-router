<?php

namespace Rockberpro\RosaRouter\Middleware;

use Rockberpro\RosaRouter\Database\Handlers\PDOApiKeysHandler;
use Rockberpro\RosaRouter\Core\Response;
use Rockberpro\RosaRouter\Core\Server;
use Rockberpro\RosaRouter\Utils\Cors;
use Rockberpro\RosaRouter\Utils\Sop;
use Rockberpro\RosaRouter\Utils\DotEnv;
use Rockberpro\RosaRouter\Jwt;

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