<?php

use Rockberpro\RestRouter\Database\Handlers\PDOApiKeysHandler;
use Rockberpro\RestRouter\Database\Handlers\PDOApiUsersHandler;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Utils\Uuid;

require_once "../vendor/autoload.php";

DotEnv::load('../.env');

$uuid = new Uuid();
$key = $uuid->uidv4Base64();

print('X-Api-Key: ' . $key . PHP_EOL);

$apiKey = new PDOApiKeysHandler();
$apiKey->addKey($key, 'generic');

print(PHP_EOL);

$apiUser = new PDOApiUsersHandler();
$apiUser->addUser('api', 'api', 'generic');
print('Username: api' . PHP_EOL);
print('Password: api' . PHP_EOL);