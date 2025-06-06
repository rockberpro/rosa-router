<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";
require_once "routes/api.php";

DotEnv::load('.env');

(new Bootstrap())->execute(null);