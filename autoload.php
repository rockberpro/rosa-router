<?php

$namespaceMap = [
    "Rockberpro\\RestRouter\\" => "src/",
    "Rockberpro\\RestRouter\\Controllers\\" => "controllers/",
    "Rockberpro\\RestRouter\\Routes\\" => "routes/"
];

spl_autoload_register(function(string $classname) use ($namespaceMap) {

    array_map(function($folder, $namespace) use ($classname) {
        $class = end(explode($namespace, $classname));
        $file = $folder . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

        $contains = str_contains($classname, end(explode($classname, $namespace)));
        if (!$contains) {
            return;
        }

        if (!file_exists($file)
        && (!interface_exists($classname) || !class_exists($classname))) {
            return;
        }

        require_once $file;

    }, $namespaceMap,array_keys($namespaceMap));

});