<?php

namespace Rockberpro\RestRouter\Service;

class Container implements ContainerInterface
{
    private static ?ContainerInterface $instance = null;
    private array $services = [];

    private function __construct() {}

    public static function getInstance(): ContainerInterface
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function setInstance(ContainerInterface $container): void
    {
        self::$instance = $container;
    }

    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     * @throws \RuntimeException
     */
    public function get(string $id)
    {
        if (!array_key_exists($id, $this->services)) {
            throw new \RuntimeException("Service '{$id}' not found in container");
        }

        $service = $this->services[$id];

        if ($service instanceof \Closure) {
            $this->services[$id] = $service($this);
            return $this->services[$id];
        }

        return $service;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
}