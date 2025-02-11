CREATE ROLE rosa WITH LOGIN PASSWORD 'rosa';
CREATE DATABASE rosa_api;
ALTER DATABASE rosa_api OWNER TO rosa;
GRANT ALL ON DATABASE rosa_api TO rosa;

DROP IF EXISTS TABLE sys_api_users;
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

DROP IF EXISTS TABLE sys_api_keys;
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

DROP IF EXISTS TABLE sys_api_logs;
CREATE TABLE sys_api_logs(
    id SERIAL NOT NULL PRIMARY KEY,
    subject TEXT NOT NULL,
    remote_address TEXT NOT NULL,
    target_address TEXT NOT NULL,
    user_agent TEXT,
    request_method TEXT NOT NULL,
    request_uri TEXT NOT NULL,
    request_body TEXT,
    endpoint TEXT NOT NULL,
    class TEXT NOT NULL,
    method TEXT NOT NULL,
    access_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX sys_api_logs_subject ON sys_api_logs(subject);
CREATE INDEX sys_api_logs_remote_address ON sys_api_logs(remote_address);
CREATE INDEX sys_api_logs_target_address ON sys_api_logs(target_address);
CREATE INDEX sys_api_logs_request_method ON sys_api_logs(request_method);
CREATE INDEX sys_api_logs_endpoint ON sys_api_logs(endpoint);
CREATE INDEX sys_api_logs_class ON sys_api_logs(class);
CREATE INDEX sys_api_logs_method ON sys_api_logs(method);
CREATE INDEX sys_api_logs_access_at ON sys_api_logs(access_at);