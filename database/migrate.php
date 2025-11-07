<?php

use Rockberpro\RosaRouter\Database\PDOConnection;
use Rockberpro\RosaRouter\Utils\DotEnv;

require_once "../vendor/autoload.php";

DotEnv::load('../.env');

$pdo = new PDOConnection();

$pdo->createStandardStatement(<<<SQL
    DROP TABLE IF EXISTS users;
    CREATE TABLE users(
        id SERIAL NOT NULL PRIMARY KEY,
        username TEXT NOT NULL,
        password TEXT NOT NULL,
        hash_alg TEXT NOT NULL,
        audience TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP
    );
    CREATE INDEX idx_users_username ON users(username);
    CREATE INDEX idx_users_audience ON users(audience);

    DROP TABLE IF EXISTS api_keys;
    CREATE TABLE api_keys(
        id SERIAL NOT NULL PRIMARY KEY,
        audience TEXT NOT NULL,
        key TEXT NOT NULL,
        hash_alg TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP
    );
    CREATE INDEX idx_api_keys_audience ON api_keys(audience);
    CREATE INDEX idx_api_keys_key ON api_keys(key);

    DROP TABLE IF EXISTS api_tokens;
    CREATE TABLE api_tokens(
        id SERIAL NOT NULL PRIMARY KEY,
        audience TEXT NOT NULL,
        type TEXT NOT NULL,
        token TEXT NOT NULL,
        hash_alg TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP
    );
    CREATE INDEX idx_api_tokens_audience ON api_tokens(audience);
    CREATE INDEX idx_api_tokens_key ON api_tokens(token);

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