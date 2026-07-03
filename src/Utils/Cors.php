<?php

namespace Rockberpro\RosaRouter\Utils;

/**
 * Cross-origin Resource Sharing
 */
class Cors
{
    public static function allowOrigin()
    {
        if (OriginPolicy::allowsAny()) {
            header('Access-Control-Allow-Origin: *');
            return;
        }

        $origin = OriginPolicy::resolve();
        if ($origin === null) {
            return;
        }

        // Reflecting a specific origin makes the response origin-dependent,
        // so it must vary on Origin to stay cache-safe.
        header('Vary: Origin', false);
        header("Access-Control-Allow-Origin: {$origin}");
    }
}
