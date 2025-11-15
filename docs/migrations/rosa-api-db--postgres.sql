CREATE ROLE admin WITH LOGIN PASSWORD 'admin';
CREATE DATABASE rosa_api;
ALTER DATABASE rosa_api OWNER TO rosa;
GRANT ALL ON DATABASE rosa_api TO rosa;

-- Ensure pgcrypto is available for gen_random_uuid() (UUID v4)
CREATE EXTENSION IF NOT EXISTS pgcrypto;

DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS logs;

CREATE TABLE users(
    id UUID NOT NULL PRIMARY KEY DEFAULT gen_random_uuid(),
    username TEXT NOT NULL,
    password TEXT NOT NULL,
    hash_alg TEXT NOT NULL,
    audience TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP
);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_audience ON users(audience);

CREATE TABLE api_keys(
    id UUID NOT NULL PRIMARY KEY DEFAULT gen_random_uuid(),
    audience TEXT NOT NULL,
    key TEXT NOT NULL,
    hash_alg TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP
);
CREATE INDEX idx_api_keys_audience ON api_keys(audience);
CREATE INDEX idx_api_keys_key ON api_keys(key);

CREATE TABLE api_tokens(
    id UUID NOT NULL PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    audience TEXT NOT NULL,
    type TEXT NOT NULL,
    token TEXT NOT NULL,
    hash_alg TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP
);
CREATE INDEX idx_api_tokens_audience ON api_tokens(audience);
CREATE INDEX idx_api_tokens_key ON api_tokens(token);

CREATE TABLE logs(
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    channel TEXT,
    level TEXT,
    message TEXT,
    context JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX logs_channel ON logs(channel);
CREATE INDEX logs_level ON logs(level);
CREATE INDEX logs_created_at ON logs(created_at);