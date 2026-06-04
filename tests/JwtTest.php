<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Jwt;
use Rockberpro\RestRouter\JwtException;

/**
 * Exercises the HS256 sign/verify round trip and the individual rejection
 * paths in Jwt::validate. All inputs are deterministic given the env below.
 */
class JwtTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('JWT_ISSUER=rosa-issuer');
        putenv('JWT_SUBJECT=rosa-subject');
        putenv('JWT_SECRET=super-secret-key');
    }

    protected function tearDown(): void
    {
        putenv('JWT_ISSUER');
        putenv('JWT_SUBJECT');
        putenv('JWT_SECRET');
    }

    public function testAccessTokenHasThreeParts(): void
    {
        $token = Jwt::getAccessToken();

        $this->assertCount(3, explode('.', $token));
    }

    public function testValidAccessTokenPassesValidation(): void
    {
        $token = Jwt::getAccessToken();

        Jwt::validate("Bearer {$token}", 'access');

        // validate() returns void; reaching here means no exception was thrown
        $this->expectNotToPerformAssertions();
    }

    public function testValidRefreshTokenPassesValidation(): void
    {
        $token = Jwt::getRefreshToken('client-app');

        Jwt::validate("Bearer {$token}", 'refresh');

        $this->expectNotToPerformAssertions();
    }

    public function testRejectsMalformedToken(): void
    {
        $this->expectException(JwtException::class);
        $this->expectExceptionMessage('Invalid token provided');

        Jwt::validate('not-a-jwt', 'access');
    }

    public function testRejectsExpiredAccessToken(): void
    {
        $past = (new DateTime())->sub(new DateInterval('PT1H'));
        $token = Jwt::getAccessToken($past);

        $this->expectException(JwtException::class);
        $this->expectExceptionMessage('Token is expired');

        Jwt::validate("Bearer {$token}", 'access');
    }

    public function testRejectsWrongIssuer(): void
    {
        $token = Jwt::getAccessToken();

        // token was signed for 'rosa-issuer'; the verifier now expects another
        putenv('JWT_ISSUER=someone-else');

        $this->expectException(JwtException::class);
        $this->expectExceptionMessage('Invalid token issuer');

        Jwt::validate("Bearer {$token}", 'access');
    }

    public function testRejectsTamperedSignature(): void
    {
        $token = Jwt::getAccessToken();

        // same claims, different signing key -> signature must not verify
        putenv('JWT_SECRET=a-different-secret');

        $this->expectException(JwtException::class);
        $this->expectExceptionMessage('Invalid token');

        Jwt::validate("Bearer {$token}", 'access');
    }
}
