<?php

namespace Rockberpro\RestRouter\Controllers;

use Rockberpro\RestRouter\Jwt;
use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Database\Models\SysApiTokens;
use Rockberpro\RestRouter\Database\Models\SysApiUsers;
use Rockberpro\RestRouter\Request;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class AuthController
{
    /**
     * Refresh token
     * 
     * @method refresh
     * @return void Response
     */
    public function refresh(Request $request)
    {
        if (DotEnv::get('API_AUTH_METHOD') != 'JWT') {
            Response::json(['message' => 'Invalid auth method'], Response::UNAUTHORIZED);
        }

        $username = $request->get('username');
        $password = $request->get('password');

        $sysApiUsers = new SysApiUsers();
        $user = $sysApiUsers->get($username);
        if (!$user) {
            Response::json(['message' => 'User does not exist'], Response::UNAUTHORIZED);
        }
        if (!hash_equals($user->password, hash('sha256', $password))) {
            Response::json(['message' => 'Invalid credentials'], Response::UNAUTHORIZED);
        }

        $sysApiTokens = new SysApiTokens();
        $last_token = $sysApiTokens->getLastValidToken($user->audience);
        if ($last_token) {
            $sysApiTokens->revokeByHash($last_token);
        }

        $refresh_token = Jwt::getRefreshToken($user->audience);
        try {
            $sysApiTokens->add($refresh_token, $user->audience);
        }
        catch (Exception $e) {
            Response::json(['message' => $e->getMessage()], Response::INTERNAL_SERVER_ERROR);
        }

        Response::json([
            'refresh-token' => "Bearer {$refresh_token}"
        ], Response::OK);
    }

    /**
     * Access token
     * 
     * @method access
     * @return void Response
     */
    public function access()
    {
        if (DotEnv::get('API_AUTH_METHOD') != 'JWT') {
            Response::json(['message' => 'Invalid auth method'], Response::UNAUTHORIZED);
        }

        /** validate */
        if (!Server::authorization()) {
            Response::json(['message' => 'Refresh-token not provided'], Response::BAD_REQUEST);
        }

        $token = explode(' ', Server::authorization())[1];
        $sysApiTokens = new SysApiTokens();
        if (!$sysApiTokens->exists($token)) {
            Response::json(['message' => 'Token is invalid'], Response::UNAUTHORIZED);
        }
        if ($sysApiTokens->isRevoked($token)) {
            Response::json(['message' => 'Token revoked'], Response::UNAUTHORIZED);
        }

        Jwt::validate(Server::authorization(), 'refresh');

        $access_token = Jwt::getAccessToken();
        Response::json([
            'access-token' => "Bearer {$access_token}"
        ], Response::OK);
    }
}