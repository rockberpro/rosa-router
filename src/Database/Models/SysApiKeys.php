<?php

namespace Rockberpro\RestRouter\Database\Models;

use Rockberpro\RestRouter\Database\PDOConnection;

use PDO;
use stdClass;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class SysApiKeys
{
    private const ENTITY = 'sys_api_keys';

    private static ?PDOConnection $pdonection = null;

    /**
     * Add a new key
     * 
     * @method add
     * @param string $key
     * @param string $audience
     * @return void
     */
    public function add($key, $audience)
    {
        if ($this->exists($key)) {
            throw new Exception('Key already in use');
        }

        $pdo = self::getConnection();

        $sysApiKey = new stdClass();
        $sysApiKey->key = hash('sha256', $key);
        $sysApiKey->hash_alg = 'sha256';
        $sysApiKey->audience = $audience;
        $sysApiKey->created_at = date('Y-m-d H:i:s');

        $pdo->beginTransaction();
        $pdo->insertObject($sysApiKey, SysApiKeys::ENTITY);
        $pdo->commitTransaction();
    }

    /**
     * Check if the key exists
     * 
     * @method exists
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        $pdo = self::getConnection();

        $pdo->createPreparedStatement("
            SELECT 1 FROM sys_api_keys WHERE key = :key
        ");
        $pdo->bindParameter(':key', hash('sha256', $key), PDO::PARAM_STR);

        return (bool) $pdo->rowCount();
    }

    /**
     * Check if the key is revoked
     * 
     * @method isRevoked
     * @param string $key
     * @return bool
     */
    public function isRevoked($key)
    {
        $pdo = self::getConnection();

        $pdo->createPreparedStatement("
            SELECT 1 FROM sys_api_keys WHERE key = :key AND revoked_at IS NULL
        ");
        $pdo->bindParameter(':key', hash('sha256', $key), PDO::PARAM_STR);

        if ($pdo->fetch()) {
            return false;
        }

        return true;
    }

    /**
     * Revoke the key
     * 
     * @method revokeByKey
     * @param string $key
     * @return void
     */
    public function revokeByKey($key)
    {
        $pdo = self::getConnection();

        $pdo->beginTransaction();
        $pdo->createPreparedStatement("
            UPDATE sys_api_keys SET revoked_at = :revoked_at WHERE key = :key
        ");
        $pdo->bindParameter(':key', hash('sha256', $key), PDO::PARAM_STR);
        $pdo->bindParameter(':revoked_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $pdo->execute();
        $pdo->commitTransaction();
    }

    /**
     * Revoke the key
     * 
     * @method revokeByHash
     * @param string $key
     * @return void
     */
    public function revokeByHash($hash)
    {
        $pdo = self::getConnection();

        $pdo->beginTransaction();
        $pdo->createPreparedStatement("
            UPDATE sys_api_keys SET revoked_at = :revoked_at WHERE key = :key
        ");
        $pdo->bindParameter(':key', $hash, PDO::PARAM_STR);
        $pdo->bindParameter(':revoked_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $pdo->execute();
        $pdo->commitTransaction();
    }

    /**
     * Singleton connection
     * 
     * @method getConnection
     * @return PDOConnection
     */
    private static  function  getConnection() : PDOConnection {
        if (is_null(self::$pdonection)) {
            self::$pdonection = new PDOConnection();
        }

        return self::$pdonection;
    }
}