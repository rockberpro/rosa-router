<?php

use Rockberpro\RosaRouter\Database\PDOConnection;
use Rockberpro\RosaRouter\Database\Handlers\PDOApiKeysHandler;
use Rockberpro\RosaRouter\Database\Handlers\PDOApiUsersHandler;
use Rockberpro\RosaRouter\Utils\DotEnv;
use Rockberpro\RosaRouter\Utils\Uuid;

require_once "../vendor/autoload.php";

DotEnv::load('../.env');

$uuid = new Uuid();
$key = $uuid->uidv4Base64();

print('X-Api-Key: ' . $key . PHP_EOL);

$apiKey = new PDOApiKeysHandler((new PDOConnection())->getPDO());
$apiKey->addKey($key, 'generic');

print(PHP_EOL);

$apiUser = new PDOApiUsersHandler((new PDOConnection())->getPDO());
$apiUser->addUser('api', 'api', 'generic');
print('Username: api' . PHP_EOL);
print('Password: api' . PHP_EOL);