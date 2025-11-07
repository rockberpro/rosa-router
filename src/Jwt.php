<?php

namespace Rockberpro\RosaRouter;

use Rockberpro\RosaRouter\Utils\DotEnv;
use DateInterval;
use DateTime;
use Exception;
use Throwable;

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
        $instance = new self();

        if (!preg_match("/Bearer\s((.*)\.(.*)\.(.*))/", $token)) {
            throw new JwtException('Invalid token provided');
        }
        
        $jwt_parts = explode('.', $token);
        $json_header = self::base64UrlDecode(explode(' ', $jwt_parts[0])[1]);
        $json_payload = self::base64UrlDecode($jwt_parts[1]);
        $signature = ($jwt_parts[2]);

        $header = json_decode($json_header, true);
        $payload = json_decode($json_payload, true);

        /** header */
        if ($header['alg'] !== 'HS256') {
            throw new JwtException('Invalid algorithm');
        }
        if ($header['typ'] !== 'JWT') {
            throw new JwtException('Invalid token type');
        }

        /** payload */
        if ($type === 'access') {
            if ($payload['exp'] < (new DateTime())->getTimestamp()) {
                throw new JwtException('Token is expired');
            }
        }
        if ($type === 'refresh') {
            if (isset($payload['exp']) && $payload['exp'] < (new DateTime())->getTimestamp()) {
                throw new JwtException('Token is expired');
            }
        }
        if ($payload['iss'] !== DotEnv::get('JWT_ISSUER')) {
            throw new JwtException('Invalid token issuer');
        }
        if ($payload['sub'] !== DotEnv::get('JWT_SUBJECT')) {
            throw new JwtException('Invalid token subject');
        }
        if ($payload['typ'] !== $type) {
            throw new JwtException('Invalid token type');
        }

        /** signature */
        $val_header = self::base64UrlEncode($json_header);
        $val_payload = self::base64UrlEncode($json_payload);
        $val_signature = hash_hmac('sha256', ($val_header.'.'.$val_payload), DotEnv::get('JWT_SECRET'), true);
        $enc_val_sig = self::base64UrlEncode($val_signature);

        if (!hash_equals($signature, $enc_val_sig)) {
            throw new JwtException('Invalid token');
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
    private static function base64UrlEncode($text) : string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    private static function base64UrlDecode(string $input): string
    {
        $replaced = str_replace(['-', '_'], ['+', '/'], $input);
        $padded = str_pad($replaced, strlen($replaced) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($padded);
    }
}

class JwtException extends Exception
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null) // Adicionado ?Throwable
    {
        parent::__construct($message, $code, $previous);
    }
}