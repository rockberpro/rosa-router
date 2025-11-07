<?php

namespace Rockberpro\RosaRouter\Utils;

class Encoding
{
    /**
     * Convert to UTF-8
     *
     * @method utf8_encode
     * @param string $input
     * @return string
     */
    public static function utf8_encode($input)
    {
        return mb_convert_encoding($input, 'UTF-8', mb_detect_encoding($input, ['ISO-8859-1', 'UTF-8', 'ASCII'], true));
    }

    /**
     * Convert to ISO-8859-1
     *
     * @method iso88591_encode
     * @param string $input
     * @return string
     */
    public static function iso88591_encode($input)
    {
        return mb_convert_encoding($input, 'ISO-8859-1', mb_detect_encoding($input, ['ISO-8859-1', 'UTF-8', 'ASCII'], true));
    }

    /**
     * Convert recursively to UTF-8
     *
     * @since 1.0
     *
     * @method utf8_encode_deep
     * @param array $input
     * @return object|array
     */
    public static function utf8_encode_deep(&$input)
    {
        if (is_string($input))
        {
            $input = mb_convert_encoding($input, 'UTF-8', mb_detect_encoding($input, ['ISO-8859-1', 'UTF-8', 'ASCII'], true));
        }
        else if (is_array($input))
        {
            foreach ($input as &$value)
            {
                self::utf8_encode_deep($value);
            }
            unset($value);
        }
        else if (is_object($input))
        {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var)
            {
                self::utf8_encode_deep($input->$var);
            }
        }

        return $input;
    }
}