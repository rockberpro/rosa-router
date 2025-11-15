<?php

namespace Rockberpro\RestRouter\Database\Handlers;

use Rockberpro\RestRouter\Database\PDOConnection;
use Exception;
use PDO;

class PDOApiTokensHandler
{
    private string $table;
    private PDO $pdo;
    private string $driverName;

    public function __construct()
    {
        $this->table = 'api_tokens';
        $this->pdo = (new PDOConnection())->getPDO();
        $this->driverName = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ?? '';
    }

    /**
     * Quote an identifier (table or column) according to the current driver.
     * Uses backticks for MySQL and double-quotes for PostgreSQL/others.
     * Supports schema-qualified identifiers (schema.table).
     */
    private function quoteId(string $identifier): string
    {
        $parts = explode('.', $identifier);
        $quoted = array_map(function ($part) {
            if ($this->driverName === 'mysql') {
                return '`' . str_replace('`', '``', $part) . '`';
            }

            return '"' . str_replace('"', '""', $part) . '"';
        }, $parts);

        return implode('.', $quoted);
    }

    /**
     * Add a new token to the database.
     * 
     * @param string $token
     * @param string $userId
     * @param string $audience
     * @param string $type
     * @throws \Exception
     * @return void
     */
    public function addToken(string $token, string $userId, string $audience, string $type): void
    {
        if ($this->exists($token)) {
            throw new Exception('Token already in use');
        }

        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');
        $c_hash = $this->quoteId('hash_alg');
        $c_user = $this->quoteId('user_id');
        $c_audience = $this->quoteId('audience');
        $c_type = $this->quoteId('type');
        $c_created = $this->quoteId('created_at');

        $sql = "INSERT INTO {$table} ({$c_token}, {$c_hash}, {$c_user}, {$c_audience}, {$c_type}, {$c_created}) 
            VALUES (:token, :hash_alg, :user_id, :audience, :type, :created_at)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':token' => hash('sha256', $token),
            ':user_id' => $userId,
            ':hash_alg' => 'sha256',
            ':audience' => $audience,
            ':type' => $type,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if a token exists in the database.
     * 
     * @param string $token
     * @return bool
     */
    public function exists(string $token): bool
    {
        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');

        $sql = "SELECT 1 FROM {$table} WHERE {$c_token} = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => hash('sha256', $token)]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Check if a token is revoked.
     * 
     * @param string $token
     * @return bool
     */
    public function isRevoked(string $token): bool
    {
        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');
        $c_revoked = $this->quoteId('revoked_at');

        $sql = "SELECT 1 FROM {$table} WHERE {$c_token} = :token AND {$c_revoked} IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => hash('sha256', $token)]);

        return !$stmt->fetchColumn();
    }

    /**
     * Revoke a token by setting the revoked_at timestamp.
     * 
     * @param string $token
     * @return void
     */
    public function revokeByToken(string $token): void
    {
        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');
        $c_revoked = $this->quoteId('revoked_at');

        $sql = "UPDATE {$table} SET {$c_revoked} = :revoked_at WHERE {$c_token} = :token";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':token' => hash('sha256', $token),
            ':revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Revoke a token by its hash.
     * 
     * @param string $hash
     * @return void
     */
    public function revokeByHash(string $hash): void
    {
        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');
        $c_revoked = $this->quoteId('revoked_at');

        $sql = "UPDATE {$table} SET {$c_revoked} = :revoked_at WHERE {$c_token} = :token";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':token' => $hash,
            ':revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Fetch a token from the database.
     * 
     * @param string $token
     * @throws \Exception
     * @return array|null
     */
    public function fetchToken(string $token): ?array
    {
        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');

        $sql = "SELECT * FROM {$table} WHERE {$c_token} = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => hash('sha256', $token)]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getUserIdByToken(string $token): ?string
    {
        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');
        $c_user = $this->quoteId('user_id');

        $sql = "SELECT {$c_user} FROM {$table} WHERE {$c_token} = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => hash('sha256', $token)]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['user_id'] ?? null;
    }

    public function getAudienceByToken(string $token): ?string
    {
        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');
        $c_audience = $this->quoteId('audience');

        $sql = "SELECT {$c_audience} FROM {$table} WHERE {$c_token} = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => hash('sha256', $token)]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['audience'] ?? null;
    }

    /**
     * Get the last valid token for a specific audience.
     * 
     * @param string $userId
     * @throws \Exception
     * @return string|null
     */
    public function getLastValidToken(string $userId): ?string
    {
        $table = $this->quoteId($this->table);
        $c_token = $this->quoteId('token');
        $c_user = $this->quoteId('user_id');
        $c_revoked = $this->quoteId('revoked_at');
        $c_created = $this->quoteId('created_at');

        $sql = "SELECT {$c_token} FROM {$table} WHERE {$c_user} = :user_id AND {$c_revoked} IS NULL 
            ORDER BY {$c_created} DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['token'] ?? null;
    }
}