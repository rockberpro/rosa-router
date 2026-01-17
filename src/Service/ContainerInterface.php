<?php

namespace Rockberpro\RestRouter\Service;

interface ContainerInterface
{
    public function set(string $id, object $service);
    public function get(string $id);
    public function has(string $id);
}