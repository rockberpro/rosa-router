<?php

namespace Rockberpro\RestRouter\Database\Handlers;

use Rockberpro\RestRouter\Database\PDOConnection;
use Exception;
use PDO;

class PDOApiTokensHandler
{
    private string $table;
    private PDO $pdo;

    public function __construct()
    {
        $this->table = 'api_tokens';
        $this->pdo = (new PDOConnection())->getPDO();
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

        $sql = "INSERT INTO {$this->table} (token, hash_alg, user_id, audience, type, created_at) 
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
        $sql = "SELECT 1 FROM {$this->table} WHERE token = :token";
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
        $sql = "SELECT 1 FROM {$this->table} WHERE token = :token AND revoked_at IS NULL";
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
        $sql = "UPDATE {$this->table} SET revoked_at = :revoked_at WHERE token = :token";
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
        $sql = "UPDATE {$this->table} SET revoked_at = :revoked_at WHERE token = :token";
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
        $sql = "SELECT * FROM {$this->table} WHERE token = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => hash('sha256', $token)]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getUserIdByToken(string $token): ?string
    {
        $sql = "SELECT user_id FROM {$this->table} WHERE token = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => hash('sha256', $token)]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['user_id'] ?? null;
    }

    public function getAudienceByToken(string $token): ?string
    {
        $sql = "SELECT audience FROM {$this->table} WHERE token = :token";
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
        $sql = "SELECT token FROM {$this->table} WHERE user_id = :user_id AND revoked_at IS NULL 
                ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['token'] ?? null;
    }
}