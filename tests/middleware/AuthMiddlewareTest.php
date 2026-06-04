<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\Response;
use Rockberpro\RestRouter\Jwt;
use Rockberpro\RestRouter\JwtException;
use Rockberpro\RestRouter\Middleware\AuthMiddleware;

/**
 * Exercises AuthMiddleware under the JWT auth method.
 *
 * Scope notes:
 *  - The "again" (rejection) path throws JwtException before any headers are
 *    emitted, so it runs cleanly in-process.
 *  - The happy path reaches Cors::allowOrigin(), which calls header(); PHPUnit
 *    will already have produced output by then, so that test runs in a separate
 *    process where headers_sent() is still false.
 *  - The KEY auth method needs a live PDO connection (PDOApiKeysHandler) and is
 *    therefore left to integration coverage, not unit-tested here.
 */
class AuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        // Sop::check() and Cors read API_ALLOW_ORIGIN; '*' short-circuits both
        // so the same-origin check never denies and no host comparison runs.
        putenv('API_ALLOW_ORIGIN=*');
        putenv('API_AUTH_METHOD=JWT');
        putenv('JWT_ISSUER=rosa-issuer');
        putenv('JWT_SUBJECT=rosa-subject');
        putenv('JWT_SECRET=super-secret-key');
    }

    protected function tearDown(): void
    {
        putenv('API_ALLOW_ORIGIN');
        putenv('API_AUTH_METHOD');
        putenv('JWT_ISSUER');
        putenv('JWT_SUBJECT');
        putenv('JWT_SECRET');
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    private function next(): Closure
    {
        return fn(Request $request): Response => new Response(['next' => true], Response::OK);
    }

    public function testRejectsMissingToken(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = '';

        $this->expectException(JwtException::class);

        (new AuthMiddleware())->handle(new Request(), $this->next());
    }

    public function testRejectsTamperedToken(): void
    {
        $token = Jwt::getAccessToken();
        // same claims, different signing key -> signature must not verify
        putenv('JWT_SECRET=a-different-secret');
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        $this->expectException(JwtException::class);

        (new AuthMiddleware())->handle(new Request(), $this->next());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testValidTokenReachesNext(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . Jwt::getAccessToken();

        $response = (new AuthMiddleware())->handle(new Request(), $this->next());

        $this->assertSame(['next' => true], $response->data);
        $this->assertSame(Response::OK, $response->status);
    }
}
