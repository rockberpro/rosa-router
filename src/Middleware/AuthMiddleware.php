<?php

namespace Rockberpro\RestRouter\Middleware;

use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\Cors;
use Rockberpro\RestRouter\Utils\Sop;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Jwt;
use Rockberpro\RestRouter\Database\Models\SysApiKeys;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @version 1.1
 * @package Rockberpro\RestRouter\Middleware
 */
class AuthMiddleware
{
    /**
     * Secure the request
     * 
     * @method handle
     * @return void
     */
    public function handle()
    {
        Sop::check();

        if (!DotEnv::get('API_AUTH_METHOD')) {
            Response::json(['message' => "Access denied"], Response::UNAUTHORIZED);
        }

        if (DotEnv::get('API_AUTH_METHOD') === 'JWT') {
            Jwt::validate(Server::authorization(), 'access');
        }

        if (DotEnv::get('API_AUTH_METHOD') === 'KEY') {
            $sysApiKey = new SysApiKeys();
            if (!$sysApiKey->exists(Server::key())) {
                Response::json(['message' => "Access denied"], Response::UNAUTHORIZED);
            }
            if ($sysApiKey->isRevoked(Server::key())) {
                Response::json(['message' => "Access denied"], Response::UNAUTHORIZED);
            }
        }

        Cors::allowOrigin();
    }
}