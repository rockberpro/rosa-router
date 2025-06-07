<?php

namespace Rockberpro\RestRouter\Handlers;

use PDO;
use Exception;

class PDOApiUsersHandler
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table = 'users')
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Add a new user to the database.
     * 
     * @param string $username
     * @param string $password
     * @param string $audience
     * @throws \Exception
     * @return void
     */
    public function addUser(string $username, string $password, string $audience): void
    {
        if ($this->exists($username)) {
            throw new Exception('User already exists');
        }

        $sql = "INSERT INTO {$this->table} (username, password, hash_alg, audience, created_at) 
                VALUES (:username, :password, :hash_alg, :audience, :created_at)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':username' => $username,
            ':password' => hash('sha256', $password),
            ':hash_alg' => 'sha256',
            ':audience' => $audience,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if a user exists by username.
     * 
     * @param string $username
     * @return bool
     */
    public function exists(string $username): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Revoke a user by setting the revoked_at timestamp.
     * 
     * @method revokeUser
     * @param string $username
     * @return void
     */
    public function revokeUser(string $username): void
    {
        $sql = "UPDATE {$this->table} SET revoked_at = :revoked_at WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':username' => $username,
            ':revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if a user is revoked.
     * 
     * @param string $username
     * @return bool
     */
    public function isRevoked(string $username): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE username = :username AND revoked_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);

        return !$stmt->fetchColumn();
    }

    /**
     * Get user details by username.
     * 
     * @param string $username
     * @return object
     */
    public function getUser(string $username): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }
}