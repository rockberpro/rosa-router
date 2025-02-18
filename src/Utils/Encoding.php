<?php

namespace Rockberpro\RestRouter\Utils;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Utils
 */
class Encoding
{
    /**
     * Convert to UTF-8
     *
     * @method encodeUTF8
     * @param string $input
     * @return string
     */
    public static function encodeUTF8($input)
    {
        return mb_convert_encoding($input, 'UTF-8', mb_detect_encoding($input, ['ISO-8859-1', 'UTF-8', 'ASCII'], true));
    }

    /**
     * Convert to ISO-8859-1
     *
     * @method encodeISO88591
     * @param string $input
     * @return string
     */
    public static function encodeISO88591($input)
    {
        return mb_convert_encoding($input, 'ISO-8859-1', mb_detect_encoding($input, ['ISO-8859-1', 'UTF-8', 'ASCII'], true));
    }

    /**
     * Convert recursively to UTF-8
     *
     * @since 1.0
     *
     * @method encodeUTF8Deep
     * @param array $input
     * @return mixed
     */
    public static function encodeUTF8Deep(&$input)
    {
        if (is_string($input))
        {
            $input = mb_convert_encoding($input, 'UTF-8', mb_detect_encoding($input, ['ISO-8859-1', 'UTF-8', 'ASCII'], true));
        }
        else if (is_array($input))
        {
            foreach ($input as &$value)
            {
                self::encodeUTF8Deep($value);
            }
            unset($value);
        }
        else if (is_object($input))
        {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var)
            {
                self::encodeUTF8Deep($input->$var);
            }
        }

        return $input;
    }
}