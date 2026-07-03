<?php

namespace Rockberpro\RosaRouter;

use Rockberpro\RosaRouter\Utils\DotEnv;
use DateInterval;
use DateTime;

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
    public static function validate($token, $type): void
    {
        if (!preg_match('/^Bearer\s+([^.\s]+)\.([^.\s]+)\.([^.\s]+)$/', $token, $matches)) {
            throw new JwtException('Invalid token provided');
        }

        [, $header_b64, $payload_b64, $signature] = $matches;

        $header = json_decode(self::base64UrlDecode($header_b64), true);
        $payload = json_decode(self::base64UrlDecode($payload_b64), true);

        if (!is_array($header) || !is_array($payload)) {
            throw new JwtException('Invalid token provided');
        }

        /** header */
        if (($header['alg'] ?? null) !== 'HS256') {
            throw new JwtException('Invalid algorithm');
        }
        if (($header['typ'] ?? null) !== 'JWT') {
            throw new JwtException('Invalid token type');
        }

        /** signature — verify over the received segments before trusting any claim */
        $val_signature = hash_hmac('sha256', ($header_b64.'.'.$payload_b64), DotEnv::get('JWT_SECRET'), true);
        $enc_val_sig = self::base64UrlEncode($val_signature);

        if (!hash_equals($enc_val_sig, $signature)) {
            throw new JwtException('Invalid token');
        }

        /** payload */
        $now = (new DateTime())->getTimestamp();
        if ($type === 'access') {
            if (!isset($payload['exp']) || $payload['exp'] < $now) {
                throw new JwtException('Token is expired');
            }
        }
        if ($type === 'refresh') {
            if (isset($payload['exp']) && $payload['exp'] < $now) {
                throw new JwtException('Token is expired');
            }
        }
        if (($payload['iss'] ?? null) !== DotEnv::get('JWT_ISSUER')) {
            throw new JwtException('Invalid token issuer');
        }
        if (($payload['sub'] ?? null) !== DotEnv::get('JWT_SUBJECT')) {
            throw new JwtException('Invalid token subject');
        }
        if (($payload['typ'] ?? null) !== $type) {
            throw new JwtException('Invalid token type');
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
    public static function getRefreshToken($audience, $expires = null): string
    {
        if (!$expires) {
            $expires = (new DateTime())->add(DateInterval::createFromDateString('7 days'));
        }

        $instance = new self();

        $header = $instance->base64UrlEncode($instance->getHeader());
        $payload = $instance->base64UrlEncode($instance->getPayload('refresh', $audience, $expires));
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
    public static function getAccessToken($expires = null): string
    {
        if (!$expires) {
            $expires = (new DateTime())->add(DateInterval::createFromDateString('30 minutes'));
        }

        $instance = new self();

        $header = $instance->base64UrlEncode($instance->getHeader());
        $payload = $instance->base64UrlEncode($instance->getPayload('access', null, $expires));
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
    private function getPayload($type, $audience = null, $expires = null)
    {
        $payload = [
            'iss' => DotEnv::get('JWT_ISSUER'),
            'sub' => DotEnv::get('JWT_SUBJECT'),
            'typ' => $type,
            'iat' => (new DateTime())->getTimestamp(),
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
    private static function base64UrlEncode($text) : string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    private static function base64UrlDecode(string $input): string
    {
        $replaced = str_replace(['-', '_'], ['+', '/'], $input);
        $remainder = strlen($replaced) % 4;
        if ($remainder) {
            $replaced .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode($replaced, true);
    }
}
