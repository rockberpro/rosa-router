<?php

namespace Rockberpro\RestRouter\Database\Models;

use Rockberpro\RestRouter\Database\PDOConnection;

use PDO;
use stdClass;
use Exception;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Alfa\RestRouter\Database\Models
 */
class SysApiUsers
{
    const ENTITY = 'sys_api_users';

    private static ?PDOConnection $pdonection = null;
   
    /**
     * Get an api-client (user)
     * 
     * @method get
     * @param string $username
     * @return stdClass
     */
    public function get($username)
    {
        $pdo = self::getConnection();

        $sysApiUser = new stdClass();
        $sysApiUser->username = ['=', $username];

        return $pdo->fetchObject($sysApiUser, SysApiUsers::ENTITY);
    }

    /**
     * Add a new api-client (user)
     * 
     * @method add
     * @param string $username
     * @param string $password
     * @param string $audience
     * @return void
     */
    public function add($username, $password, $audience)
    {
        if ($this->exists($username)) {
            throw new Exception('User already exists');
        }

        $pdo = self::getConnection();

        $sysApiUser = new stdClass();
        $sysApiUser->username = $username;
        $sysApiUser->password = hash('sha256', $password);
        $sysApiUser->hash_alg = 'sha256';
        $sysApiUser->audience = $audience;
        $sysApiUser->created_at = date('Y-m-d H:i:s');

        $pdo->beginTransaction();
        $pdo->insertObject($sysApiUser, SysApiUsers::ENTITY);
        $pdo->commitTransaction();
    }

    /**
     * Check if the user already exists
     * 
     * @method exists
     * @param string $username
     * @return bool
     */
    public function exists($username)
    {
        $pdo = self::getConnection();

        $pdo->createPreparedStatement("
            SELECT 1 FROM sys_api_users WHERE username = :username
        ");
        $pdo->bindParameter(':username', $username, PDO::PARAM_STR);

        return (bool) $pdo->rowCount();
    }

    /**
     * Revokes the user
     * 
     * @method revoke
     * @param string $username
     * @return void
     */
    public function revoke($username)
    {
        $pdo = self::getConnection();
        
        $pdo->beginTransaction();
        $pdo->createPreparedStatement("
            UPDATE sys_api_users SET revoked_at = :revoked_at WHERE username = :username
        ");
        $pdo->bindParameter(':username', $username, PDO::PARAM_STR);
        $pdo->bindParameter(':revoked_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $pdo->execute();
        $pdo->commitTransaction();
    }

    /**
     * Check if the user is revoked
     * 
     * @method isRevoked
     * @param string $username
     * @return bool
     */
    public function isRevoked($username)
    {
        $pdo = self::getConnection();

        $pdo->createPreparedStatement("
            SELECT 1 FROM sys_api_users WHERE username = :username AND revoked_at IS NULL
        ");
        $pdo->bindParameter(':username', $username, PDO::PARAM_STR);

        if ($pdo->fetch()) {
            return false;
        }

        return true;
    }

    /**
     * Singleton connection
     * 
     * @method getConnection
     * @return PDOConnection
     */
    private static  function getConnection() : PDOConnection {
        if (is_null(self::$pdonection)) {
            self::$pdonection = new PDOConnection();
        }

        return self::$pdonection;
    }
}