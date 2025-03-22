<?php

namespace Rockberpro\RestRouter\Controllers;

use Rockberpro\RestRouter\Jwt;
use Rockberpro\RestRouter\JwtException;
use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\Controllers\Controller;
use Rockberpro\RestRouter\Database\Models\SysApiTokens;
use Rockberpro\RestRouter\Database\Models\SysApiUsers;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class AuthController extends Controller
{
    /**
     * Refresh token
     * 
     * @method refresh
     * @return Response
     */
    public function refresh(Request $request)
    {
        if (DotEnv::get('API_AUTH_METHOD') != 'JWT') {
            return $this->response(['message' => 'Invalid auth method'], Response::UNAUTHORIZED);
        }

        $username = $request->get('username');
        $password = $request->get('password');

        $sysApiUsers = new SysApiUsers();
        $user = $sysApiUsers->get($username);
        if (!$user) {
            return $this->response(['message' => 'User does not exist'], Response::UNAUTHORIZED);
        }
        if (!hash_equals($user->password, hash('sha256', $password))) {
            return $this->response(['message' => 'Invalid credentials'], Response::UNAUTHORIZED);
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
            return $this->response(['message' => $e->getMessage()], Response::INTERNAL_SERVER_ERROR);
        }

        return $this->response([
            'refresh-token' => "Bearer {$refresh_token}"
        ], Response::OK);
    }

    /**
     * Access token
     * 
     * @method access
     * @return Response
     */
    public function access()
    {
        if (DotEnv::get('API_AUTH_METHOD') != 'JWT') {
            return $this->response(['message' => 'Invalid auth method'], Response::UNAUTHORIZED);
        }

        /** validate */
        if (!Server::authorization()) {
            return $this->response(['message' => 'Refresh-token not provided'], Response::BAD_REQUEST);
        }

        $token = explode(' ', Server::authorization())[1];
        $sysApiTokens = new SysApiTokens();
        if (!$sysApiTokens->exists($token)) {
            return $this->response(['message' => 'Token is invalid'], Response::UNAUTHORIZED);
        }
        if ($sysApiTokens->isRevoked($token)) {
            return $this->response(['message' => 'Token revoked'], Response::UNAUTHORIZED);
        }

        try {
            Jwt::validate(Server::authorization(), 'refresh');
        }
        catch(JwtException $e) {
            return new Response(['message' => $e->getMessage()], Response::UNAUTHORIZED);
        }

        $access_token = Jwt::getAccessToken();
        return $this->response([
            'access-token' => "Bearer {$access_token}"
        ], Response::OK);
    }
}