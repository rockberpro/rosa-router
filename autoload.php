<?php

$namespaceMap = [
    "Rockberpro\\RestRouter\\" => "src/",
    "Rockberpro\\RestRouter\\Controllers\\" => "controllers/",
    "Rockberpro\\RestRouter\\Routes\\" => "routes/"
];

spl_autoload_register(function(string $classname) use ($namespaceMap) {

    foreach($namespaceMap as $namespace => $folder)
    {
        $class = explode($namespace, $classname);
        if (!isset($class[1])) {
            return;
        }

        $file = $folder . str_replace('\\', DIRECTORY_SEPARATOR, $class[1]) . '.php';
        if (!file_exists($file)) {
            continue;
        }

        require_once $file;

        if (!interface_exists($classname) && !class_exists($classname)) {
            return;
        }
    }

});