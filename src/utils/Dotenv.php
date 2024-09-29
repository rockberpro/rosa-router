<?php

namespace Rosa\Router\Utils;

use Exception;
use Throwable;

class DotEnv
{
    public static function load($path)
    {
        try
        {
            $env = parse_ini_file($path);
        }
        catch(Throwable $th)
        {
            throw new Exception($th->getMessage());
        }

        foreach($env as $key => $value)
        {
            self::put($key, $value);
        }
    }

    private static function put($key, $value)
    {
        putenv("$key=$value");
    }

    public static function get($key)
    {
        return getenv($key);
    }
}