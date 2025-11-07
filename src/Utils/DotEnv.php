<?php

namespace Rockberpro\RestRouter\Utils;

use Throwable;

class DotEnv
{
    private static \Symfony\Component\Dotenv\Dotenv $dotEnv;

    /**
     * Load the .env file. By default this will enable putenv() (so getenv() returns values)
     * and will overload existing environment variables. If you want the original
     * non-overriding behaviour, pass $override = false and $usePutenv = false.
     *
     * @param string $path
     * @param bool $override Whether to overwrite existing environment vars (defaults to true)
     * @param bool $usePutenv Whether to call putenv() so getenv() reflects the values (defaults to true)
     */
    public static function load($path, bool $override = true, bool $usePutenv = true)
    {
        try {
            $dotEnv = self::getInstance();
            if ($usePutenv) {
                $dotEnv->usePutenv(true);
            }

            if ($override) {
                $dotEnv->overload($path);
            } else {
                $dotEnv->load($path);
            }
        }
        catch(Throwable $th) {
            throw new DotEnvException($th->getMessage());
        }
    }

    public static function get($key)
    {
        $value = getenv($key);
        if ($value === false) {
            throw new DotEnvException("The environment variable '{$key}' was not found.");
        }
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

    private static function getInstance(): \Symfony\Component\Dotenv\Dotenv
    {
        if (!isset(self::$dotEnv)) {
            self::$dotEnv = new \Symfony\Component\Dotenv\Dotenv();
        }
        return self::$dotEnv;
    }
}

final class DotEnvException extends \RuntimeException {}