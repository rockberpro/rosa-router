<?php

namespace Rockberpro\RosaRouter\Utils;

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
        // An env file is optional: variables may already be provided by the
        // real environment. A missing file is skipped silently; a file that
        // exists but cannot be read is a genuine error.
        if (!is_file($path)) {
            return;
        }
        if (!is_readable($path)) {
            throw new IniEnvException("The INI file '{$path}' is not readable.");
        }

        $data = parse_ini_file($path, true, INI_SCANNER_TYPED);
        if ($data === false) {
            throw new IniEnvException("Failed to parse INI file '{$path}'.");
        }

        foreach ($data as $section => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $sectionValue) {
                    self::put($key, $sectionValue);
                }
            } else {
                self::put($section, $value);
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
        return EnvValue::coerce($value);
    }

    protected static function put(string $key, $value): void
    {
        if (is_array($value)) {
            $val = json_encode($value);
        } elseif (is_bool($value)) {
            $val = $value ? '1' : '0';
        } else {
            $val = (string) $value;
        }
        putenv("{$key}={$val}");
    }
}
