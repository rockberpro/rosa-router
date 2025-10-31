<?php

use Rockberpro\RestRouter\Bootstrap;

require_once "vendor/autoload.php";
require_once "routes/api.php";

Bootstrap::setup();
Bootstrap::stateless();
