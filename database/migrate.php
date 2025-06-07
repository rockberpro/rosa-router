<?php

use Rockberpro\RestRouter\Database\PDOConnection;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "../vendor/autoload.php";

DotEnv::load('../.env');

$pdo = new PDOConnection();

$pdo->createStandardStatement(<<<SQL
    DROP TABLE IF EXISTS  sys_api_users;
    CREATE TABLE sys_api_users(
        id SERIAL NOT NULL PRIMARY KEY,
        username TEXT NOT NULL,
        password TEXT NOT NULL,
        hash_alg TEXT NOT NULL,
        audience TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP
    );
    CREATE INDEX idx_sys_api_users_username ON sys_api_users(username);
    CREATE INDEX idx_sys_api_users_audience ON sys_api_users(audience);

    DROP TABLE IF EXISTS sys_api_keys;
    CREATE TABLE sys_api_keys(
        id SERIAL NOT NULL PRIMARY KEY,
        audience TEXT NOT NULL,
        key TEXT NOT NULL,
        hash_alg TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP
    );
    CREATE INDEX idx_sys_api_keys_audience ON sys_api_keys(audience);
    CREATE INDEX idx_sys_api_keys_key ON sys_api_keys(key);

    DROP TABLE IF EXISTS sys_api_tokens;
    CREATE TABLE sys_api_tokens(
        id SERIAL NOT NULL PRIMARY KEY,
        audience TEXT NOT NULL,
        type TEXT NOT NULL,
        token TEXT NOT NULL,
        hash_alg TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP
    );
    CREATE INDEX sys_api_tokens_audience ON sys_api_tokens(audience);
    CREATE INDEX sys_api_tokens_key ON sys_api_tokens(token);

    DROP TABLE IF EXISTS logs;
    CREATE TABLE logs(
        id SERIAL PRIMARY KEY,
        channel TEXT,
        level TEXT,
        message TEXT,
        context TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX logs_channel ON logs(channel);
    CREATE INDEX logs_level ON logs(level);
    CREATE INDEX logs_created_at ON logs(created_at);
SQL);

$pdo->execute();