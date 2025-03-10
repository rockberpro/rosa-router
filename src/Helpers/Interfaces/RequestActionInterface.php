<?php

namespace Rockberpro\RestRouter\Helpers\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Helpers\Interfaces
 */
interface RequestActionInterface
{
    public function getMethod(): string;
    public function setMethod($method): void;
    public function getClosure();
    public function setClosure($closure): void;
    public function getRoute(): array;
    public function setRoute($route): void;
    public function getClass(): string;
    public function setClass($class): void;
    public function getUri(): string;
    public function setUri($uri): void;
}