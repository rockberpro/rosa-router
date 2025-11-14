<?php

namespace Rockberpro\RestRouter\Utils;

use IniEnvException;

/**
 * Simple INI-based environment loader compatible with DotEnv::get usage.
 */
class IniEnv
{
    /**
     * Load environment variables from an INI file.
     * Throws IniEnvException on failure.
     *
     * @param string $path
     * @return void
     * @throws IniEnvException
     */
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new IniEnvException("The INI file '{$path}' was not found or is not readable.");
        }

        $data = parse_ini_file($path, true, INI_SCANNER_TYPED);
        if ($data === false) {
            throw new IniEnvException("Failed to parse INI file '{$path}'.");
        }

        foreach ($data as $section => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    self::put($key, $value);
                }
            } else {
                self::put($section, $data);
            }
        }
    }

    /**
     * Retrieve a value loaded via IniEnv (or any environment variable).
     * Mirrors DotEnv::get behaviour: throws if not found and coerces booleans.
     *
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        $value = getenv($key);
        if ($value === false) {
            throw new IniEnvException("The environment variable '{$key}' was not found.");
        }
        $v = strtolower(trim($value));
        if (in_array($v, ['1', 'true', 'on', 'yes', 'y'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'off', 'no', 'n'], true)) {
            return false;
        }
        return $value;
    }

    protected static function put(string $key, $value): void
    {
        $val = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
        putenv("{$key}={$val}");
    }
}
