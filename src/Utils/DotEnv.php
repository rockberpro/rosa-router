<?php

namespace Rockberpro\RestRouter\Utils;

use Throwable;

class DotEnv
{
    private static \Symfony\Component\Dotenv\Dotenv $dotEnv;

    public static function load($path)
    {
        try {
            $dotEnv = self::getInstance();
            $dotEnv->load($path);
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
        return EnvValue::coerce($value);
    }

    private static function getInstance(): \Symfony\Component\Dotenv\Dotenv
    {
        if (!isset(self::$dotEnv)) {
            self::$dotEnv = new \Symfony\Component\Dotenv\Dotenv();
        }
        return self::$dotEnv;
    }
}
