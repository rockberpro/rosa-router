<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Response;
use DateInterval;
use DateTime;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class Jwt
{
    /**
     * Validate JWT token
     * 
     * @method validate
     * @param string $token
     * @param string $type
     * @return void
     */
    public static function validate($token, $type)
    {
        $instance = new self();

        if (!preg_match("/Bearer\s((.*)\.(.*)\.(.*))/", $token)) {
            Response::json(['message' => 'Invalid token provided'], 401);
        }

        $jwt_parts = explode('.', $token);
        $json_header = base64_decode(explode(' ', $jwt_parts[0])[1]);
        $json_payload = base64_decode($jwt_parts[1]);
        $signature = ($jwt_parts[2]);

        $header = json_decode($json_header, true);
        $payload = json_decode($json_payload, true);

        /** header */
        if ($header['alg'] !== 'HS256') {
            Response::json(['message' => 'Invalid algorithm'], Response::UNAUTHORIZED);
        }
        if ($header['typ'] !== 'JWT') {
            Response::json(['message' => 'Invalid token type'], Response::UNAUTHORIZED);
        }

        /** payload */
        if ($type === 'access') {
            if ($payload['exp'] < (new DateTime())->getTimestamp()) {
                Response::json(['message' => 'Token is expired'], Response::UNAUTHORIZED);
            }
        }
        if ($type === 'refresh') {
            if (isset($payload['exp']) && $payload['exp'] < (new DateTime())->getTimestamp()) {
                Response::json(['message' => 'Token is expired'], Response::UNAUTHORIZED);
            }
        }
        if ($payload['iss'] !== DotEnv::get('JWT_ISSUER')) {
            Response::json(['message' => 'Invalid token issuer'], Response::UNAUTHORIZED);
        }
        if ($payload['sub'] !== DotEnv::get('JWT_SUBJECT')) {
            Response::json(['message' => 'Invalid token subject'], Response::UNAUTHORIZED);
        }
        if ($payload['typ'] !== $type) {
            Response::json(['message' => 'Invalid token type'], Response::UNAUTHORIZED);
        }

        /** signature */
        $val_header = $instance->base64UrlEncode($json_header);
        $val_payload = $instance->base64UrlEncode($json_payload);
        $val_signature = hash_hmac('sha256', ($val_header.'.'.$val_payload), DotEnv::get('JWT_SECRET'), true);
        $enc_val_sig = $instance->base64UrlEncode($val_signature);

        if (!hash_equals($signature, $enc_val_sig)) {
            Response::json(['message' => 'Invalid token'], Response::UNAUTHORIZED);
        }
    }

    /**
     * Get JWT refresh token
     * 
     * @method getToken
     * @param string $audience
     * @param DateTime|null $expires
     * @return string token
     */
    public static function getRefreshToken($audience, $expires = null)
    {
        if (!$expires) {
            $expires = (new DateTime())->add(DateInterval::createFromDateString('7 days'));
        }

        $instance = new self();

        $header = $instance->base64UrlEncode($instance->getHeader());
        $payload = $instance->base64UrlEncode($instance->getPayload($expires, 'refresh', $audience));
        $signature = hash_hmac('sha256', ($header.'.'.$payload), DotEnv::get('JWT_SECRET'), true);
        $enc_sig = $instance->base64UrlEncode($signature);

        return "{$header}.{$payload}.{$enc_sig}";
    }

    /**
     * Get JWT access token
     * 
     * @method getAccessToken
     * @param DateTime|null $expires
     * @return string token
     */
    public static function getAccessToken($expires = null)
    {
        if (!$expires) {
            $expires = (new DateTime())->add(DateInterval::createFromDateString('30 minutes'));
        }

        $instance = new self();

        $header = $instance->base64UrlEncode($instance->getHeader());
        $payload = $instance->base64UrlEncode($instance->getPayload($expires, 'access'));
        $signature = hash_hmac('sha256', ($header.'.'.$payload), DotEnv::get('JWT_SECRET'), true);
        $enc_sig = $instance->base64UrlEncode($signature);

        return "{$header}.{$payload}.{$enc_sig}";
    }

    /**
     * Get JWT header
     * 
     * @method getHeader
     * @return string header
     */
    private function getHeader()
    {
        return json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]);
    }

    /**
     * Get JWT payload
     * 
     * @method getPayload
     * @param DateTime|null $expires
     * @param string $type
     * @param string $audience
     * @return string payload
     */
    private function getPayload($expires = null, $type, $audience = null)
    {
        $payload = [
            'iss' => DotEnv::get('JWT_ISSUER'),
            'sub' => DotEnv::get('JWT_SUBJECT'),
            'typ' => $type,
            'iat' => date('Y-m-d H:i:s'),
        ];

        if ($expires) {
            $payload['exp'] = $expires->getTimestamp();
        }

        if ($audience) {
            $payload['aud'] = $audience;
        }

        return json_encode($payload);
    }

    /**
     * Base64 URL encode
     * 
     * @method base64UrlEncode
     * @param string $text
     * @return string
     */
    private function base64UrlEncode($text) : string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }
}