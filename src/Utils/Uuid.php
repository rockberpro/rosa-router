<?php

namespace Rockberpro\RestRouter\Utils;

/**
 * Helper for generating UUIDs
 * 
 * @source https://www.uuidgenerator.net/dev-corner/php
 * 
 * @author Samuel Oberger Rockenbach
 * @since dec-2024
 */
class Uuid
{
    /**
     * Generate UUID v4
     * 
     * @method uidv4
     * @param string|null $data
     * @return string
     */
    public static function uidv4($data = null)
    {
        /// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        /// Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        /// Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        /// Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate UUID v4 and encode it in base64
     * 
     * @method uidv4Base64
     * @param string|null $data
     * @return string
     */
    public static function uidv4Base64($data = null)
    {
        return base64_encode(self::uidv4($data));
    }
}
