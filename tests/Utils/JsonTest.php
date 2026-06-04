<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Utils\Json;

class JsonTest extends TestCase
{
    public function testValidJsonValues(): void
    {
        $this->assertTrue(Json::isJson('{"a":1}'));
        $this->assertTrue(Json::isJson('[1,2,3]'));
        $this->assertTrue(Json::isJson('"a string"'));
        $this->assertTrue(Json::isJson('42'));
        $this->assertTrue(Json::isJson('true'));
        $this->assertTrue(Json::isJson('null'));
    }

    public function testInvalidJsonValues(): void
    {
        $this->assertFalse(Json::isJson('{a:1}'));
        $this->assertFalse(Json::isJson("{'a':1}"));
        $this->assertFalse(Json::isJson('{"a":}'));
        $this->assertFalse(Json::isJson(''));
        $this->assertFalse(Json::isJson('not json'));
    }
}
