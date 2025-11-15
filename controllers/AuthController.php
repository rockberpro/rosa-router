<?php

namespace Rockberpro\RosaRouter\Controllers;

use Rockberpro\RosaRouter\Database\Handlers\PDOApiTokensHandler;
use Rockberpro\RosaRouter\Database\Handlers\PDOApiUsersHandler;
use Rockberpro\RosaRouter\Utils\DotEnv;
use Rockberpro\RosaRouter\Core\Request;
use Rockberpro\RosaRouter\Core\Response;
use Rockberpro\RosaRouter\Core\Server;
use Rockberpro\RosaRouter\JwtException;
use Rockberpro\RosaRouter\Jwt;
use Throwable;

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

        $apiUsers = new PDOApiUsersHandler();
        $user = $apiUsers->getUser($username);
        if (!$user) {
            return $this->response(['message' => 'User does not exist'], Response::UNAUTHORIZED);
        }
        if (!password_verify($password, $user->password)) {
            return $this->response(['message' => 'Invalid credentials'], Response::UNAUTHORIZED);
        }

        $apiTokens = new PDOApiTokensHandler();
        $hash = $apiTokens->getLastValidToken($user->id);
        if ($hash) {
            $apiTokens->revokeByHash($hash);
        }

        $refresh_token = Jwt::getRefreshToken($user->audience);
        $access_token = Jwt::getAccessToken();
        try {
            $apiTokens->addToken($refresh_token, $user->id, $user->audience, 'refresh');
        }
        catch (Throwable $e) {
            return $this->response(['message' => $e->getMessage()], Response::INTERNAL_SERVER_ERROR);
        }

        return $this->response([
            'access-token' => "Bearer {$access_token}",
            'refresh-token' => "Bearer {$refresh_token}",
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
        $apiTokens = new PDOApiTokensHandler();
        if (!$apiTokens->exists($token)) {
            return $this->response(['message' => 'Token is invalid'], Response::UNAUTHORIZED);
        }
        if ($apiTokens->isRevoked($token)) {
            return $this->response(['message' => 'Token revoked'], Response::UNAUTHORIZED);
        }

        try {
            Jwt::validate(Server::authorization(), 'refresh');
        }
        catch(JwtException $e) {
            return new Response(['message' => $e->getMessage()], Response::UNAUTHORIZED);
        }

        $userId = $apiTokens->getUserIdByToken($token);
        if (!$userId) {
            return $this->response(['message' => 'User not found for token'], Response::UNAUTHORIZED);
        }
        $audience = $apiTokens->getAudienceByToken($token);
        if (!$audience) {
            return $this->response(['message' => 'Audience not found for token'], Response::UNAUTHORIZED);
        }

        $apiTokens->revokeByToken($token);

        $access_token = Jwt::getAccessToken();
        $refresh_token = Jwt::getRefreshToken($audience);

        $apiTokens->addToken($refresh_token, $userId, $audience, 'refresh');

        return $this->response([
            'access-token' => "Bearer {$access_token}",
            'refresh-token' => "Bearer {$refresh_token}",
        ], Response::OK);
    }
}