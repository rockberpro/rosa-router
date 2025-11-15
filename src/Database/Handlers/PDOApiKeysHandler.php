<?php

namespace Rockberpro\RosaRouter\Database\Handlers;

use Rockberpro\RosaRouter\Database\PDOConnection;
use PDO;

class PDOApiKeysHandler
{
    private string $table;
    private PDO $pdo;
    private string $driverName;

    public function __construct()
    {
        $this->table = 'api_keys';
        $this->pdo = (new PDOConnection())->getPDO();
        $this->driverName = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ?? '';
    }

    /**
     * Quote an identifier (table or column) according to the current driver.
     * Uses backticks for MySQL and double-quotes for PostgreSQL/others.
     */
    private function quoteId(string $identifier): string
    {
        // handle schema.table identifiers by quoting each part separately
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
     * Add a new API key to the database.
     *
     * @param string $key
     * @param string $audience
     * @throws \Exception
     */
    public function addKey(string $key, string $audience): void
    {
        if ($this->exists($key)) {
            throw new \Exception('Key already in use');
        }

        $table = $this->quoteId($this->table);
        $c_key = $this->quoteId('key');
        $c_hash = $this->quoteId('hash_alg');
        $c_audience = $this->quoteId('audience');
        $c_created = $this->quoteId('created_at');

        $sql = "INSERT INTO {$table} ({$c_key}, {$c_hash}, {$c_audience}, {$c_created}) VALUES (:key, :hash_alg, :audience, :created_at)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':key' => hash('sha256', $key),
            ':hash_alg' => 'sha256',
            ':audience' => $audience,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if an API key exists in the database.
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        $table = $this->quoteId($this->table);
        $c_key = $this->quoteId('key');
        $sql = "SELECT 1 FROM {$table} WHERE {$c_key} = :key";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':key' => hash('sha256', $key)]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Check if an API key is revoked.
     *
     * @param string $key
     * @return bool
     */
    public function isRevoked(string $key): bool
    {
        $table = $this->quoteId($this->table);
        $c_key = $this->quoteId('key');
        $c_revoked = $this->quoteId('revoked_at');
        $sql = "SELECT 1 FROM {$table} WHERE {$c_key} = :key AND {$c_revoked} IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':key' => hash('sha256', $key)]);

        return !$stmt->fetchColumn();
    }

    /**
     * Revoke an API key by setting the revoked_at timestamp.
     *
     * @param string $key
     * @return void
     */
    public function revokeByKey(string $key): void
    {
        $table = $this->quoteId($this->table);
        $c_key = $this->quoteId('key');
        $c_revoked = $this->quoteId('revoked_at');
        $sql = "UPDATE {$table} SET {$c_revoked} = :revoked_at WHERE {$c_key} = :key";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':key' => hash('sha256', $key),
            ':revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Revoke an API key by its hash.
     *
     * @param string $hash
     * @return void
     */
    public function revokeByHash(string $hash): void
    {
        $table = $this->quoteId($this->table);
        $c_key = $this->quoteId('key');
        $c_revoked = $this->quoteId('revoked_at');
        $sql = "UPDATE {$table} SET {$c_revoked} = :revoked_at WHERE {$c_key} = :key";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':key' => $hash,
            ':revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }
}