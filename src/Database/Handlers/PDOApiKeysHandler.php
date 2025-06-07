<?php

namespace Rockberpro\RestRouter\Database\Handlers;

use PDO;

class PDOApiKeysHandler
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table = 'api_keys')
    {
        $this->pdo = $pdo;
        $this->table = $table;
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

        $sql = "INSERT INTO {$this->table} (key, hash_alg, audience, created_at) VALUES (:key, :hash_alg, :audience, :created_at)";
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
        $sql = "SELECT 1 FROM {$this->table} WHERE key = :key";
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
        $sql = "SELECT 1 FROM {$this->table} WHERE key = :key AND revoked_at IS NULL";
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
        $sql = "UPDATE {$this->table} SET revoked_at = :revoked_at WHERE key = :key";
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
        $sql = "UPDATE {$this->table} SET revoked_at = :revoked_at WHERE key = :key";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':key' => $hash,
            ':revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }
}