<?php

namespace Rockberpro\RestRouter\Helpers\Interfaces;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
interface RequestActionInterface
{
    public function getMethod();
    public function setMethod($method);
    public function getClosure();
    public function setClosure($closure);
    public function getRoute();
    public function setRoute($route);
    public function getClass();
    public function setClass($class);
    public function getUri();
    public function setUri($uri);
}