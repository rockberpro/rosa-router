<?php

namespace Rockberpro\RestRouter\Utils;

/**
 * Shared coercion for environment values, used by DotEnv and IniEnv so their
 * truthy/falsy handling stays identical.
 */
class EnvValue
{
    /**
     * Coerce a raw environment string into a bool when it reads as one,
     * otherwise return the original string untouched.
     *
     * @param string $value
     * @return bool|string
     */
    public static function coerce(string $value)
    {
        $v = strtolower(trim($value));
        if (in_array($v, ['1', 'true', 'on', 'yes', 'y'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'off', 'no', 'n'], true)) {
            return false;
        }
        // other values
        return $value;
    }
}
