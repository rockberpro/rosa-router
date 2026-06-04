<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RosaRouter\Utils\Uuid;

class UuidTest extends TestCase
{
    public function testGeneratesValidV4Format(): void
    {
        $uuid = Uuid::uidv4();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function testVersionAndVariantBitsAreSet(): void
    {
        // Feed all-zero bytes so only the version/variant nibbles are non-zero.
        $uuid = Uuid::uidv4(str_repeat("\x00", 16));

        $this->assertSame('00000000-0000-4000-8000-000000000000', $uuid);
    }

    public function testIsDeterministicForGivenData(): void
    {
        $data = random_bytes(16);

        $this->assertSame(Uuid::uidv4($data), Uuid::uidv4($data));
    }

    public function testGeneratesDistinctValues(): void
    {
        $this->assertNotSame(Uuid::uidv4(), Uuid::uidv4());
    }

    public function testBase64VariantDecodesToTheSameUuid(): void
    {
        $data = random_bytes(16);

        $this->assertSame(
            Uuid::uidv4($data),
            base64_decode(Uuid::uidv4Base64($data))
        );
    }
}
