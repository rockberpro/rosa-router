<?php

namespace Rockberpro\RosaRouter\Database\Handlers;

use Rockberpro\RosaRouter\Database\PDOConnection;
use Exception;
use PDO;

class PDOApiUsersHandler
{
    private string $table;
    private PDO $pdo;
    private string $driverName;

    public function __construct()
    {
        $this->table = 'users';
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

        $table = $this->quoteId($this->table);
        $c_username = $this->quoteId('username');
        $c_password = $this->quoteId('password');
        $c_hash = $this->quoteId('hash_alg');
        $c_audience = $this->quoteId('audience');
        $c_created = $this->quoteId('created_at');

        $sql = "INSERT INTO {$table} ({$c_username}, {$c_password}, {$c_hash}, {$c_audience}, {$c_created}) 
            VALUES (:username, :password, :hash_alg, :audience, :created_at)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':username' => $username,
            ':password' => password_hash($password, PASSWORD_BCRYPT),
            ':hash_alg' => 'bcrypt',
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
        $table = $this->quoteId($this->table);
        $c_username = $this->quoteId('username');
        $sql = "SELECT 1 FROM {$table} WHERE {$c_username} = :username";
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
        $table = $this->quoteId($this->table);
        $c_username = $this->quoteId('username');
        $c_revoked = $this->quoteId('revoked_at');
        $sql = "UPDATE {$table} SET {$c_revoked} = :revoked_at WHERE {$c_username} = :username";
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
        $table = $this->quoteId($this->table);
        $c_username = $this->quoteId('username');
        $c_revoked = $this->quoteId('revoked_at');
        $sql = "SELECT 1 FROM {$table} WHERE {$c_username} = :username AND {$c_revoked} IS NULL";
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
    public function getUser(string $username): ?object
    {
        $table = $this->quoteId($this->table);
        $c_username = $this->quoteId('username');
        $sql = "SELECT * FROM {$table} WHERE {$c_username} = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);

      return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }
}