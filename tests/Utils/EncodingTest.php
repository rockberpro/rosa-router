<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Utils\Encoding;

class EncodingTest extends TestCase
{
    public function testUtf8EncodeConvertsLatin1ToUtf8(): void
    {
        $latin1 = "\xE9"; // 'é' in ISO-8859-1

        $this->assertSame('é', Encoding::utf8_encode($latin1));
    }

    public function testIso88591EncodeIsInverseOfUtf8(): void
    {
        $utf8 = 'é';

        $this->assertSame("\xE9", Encoding::iso88591_encode($utf8));
    }

    public function testAsciiIsUnchanged(): void
    {
        $this->assertSame('hello', Encoding::utf8_encode('hello'));
    }

    public function testUtf8EncodeDeepWalksNestedArrays(): void
    {
        $input = ['a' => "\xE9", 'nested' => ['b' => "\xE9", 'c' => 'plain']];

        Encoding::utf8_encode_deep($input);

        $this->assertSame('é', $input['a']);
        $this->assertSame('é', $input['nested']['b']);
        $this->assertSame('plain', $input['nested']['c']);
    }

    public function testUtf8EncodeDeepWalksObjectProperties(): void
    {
        $input = (object) ['name' => "\xE9", 'keep' => 'x'];

        Encoding::utf8_encode_deep($input);

        $this->assertSame('é', $input->name);
        $this->assertSame('x', $input->keep);
    }
}
