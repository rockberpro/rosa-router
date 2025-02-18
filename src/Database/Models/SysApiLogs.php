<?php

namespace Rockberpro\RestRouter\Database\Models;

use Rockberpro\RestRouter\Database\PDOConnection;
use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\Server;
use PDO;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Database\Models
 */
class SysApiLogs
{
    const ENTITY = 'sys_api_logs';

    private const SUBJECT = 'rosa-router';

    private static ?PDOConnection $pdonection = null;

    /**
     * Adds a new log
     * 
     * @method add
     * @param Request $request
     * @return void
     */
    public static function write(Request $request)
    {
        $subject = SysApiLogs::SUBJECT;
        $remote_address = Server::remoteAddress();
        $target_address = Server::targetAddress();
        $user_agent = Server::userAgent();
        $request_method = Server::requestMethod();

        $request_uri = Server::requestUri();
        $request_body = json_encode($request->getParameters());

        $endpoint = $request->getAction()->getUri();
        $class = $request->getAction()->getClass();
        $method = $request->getAction()->getMethod();

        $access_at = date('Y-m-d H:i:s');

        $pdo = self::getConnection();
        $pdo->beginTransaction();
        $pdo->createPreparedStatement("
            INSERT INTO sys_api_logs
            (
                subject,
                remote_address,
                target_address,
                user_agent,
                request_method,
                request_uri,
                request_body,
                endpoint,
                class,
                method,
                access_at
            )
            VALUES
            (
                :subject,
                :remote_address,
                :target_address,
                :user_agent,
                :request_method,
                :request_uri,
                :request_body,
                :endpoint,
                :class,
                :method,
                :access_at
            )
        ");
        $pdo->bindParameter(':subject', $subject, PDO::PARAM_STR);
        $pdo->bindParameter(':remote_address', $remote_address, PDO::PARAM_STR);
        $pdo->bindParameter(':target_address', $target_address, PDO::PARAM_STR);
        $pdo->bindParameter(':user_agent', $user_agent, PDO::PARAM_STR);
        $pdo->bindParameter(':request_method', $request_method, PDO::PARAM_STR);
        $pdo->bindParameter(':request_uri', $request_uri, PDO::PARAM_STR);
        $pdo->bindParameter(':request_body', $request_body, PDO::PARAM_STR);
        $pdo->bindParameter(':endpoint', $endpoint, PDO::PARAM_STR);
        $pdo->bindParameter(':class', $class, PDO::PARAM_STR);
        $pdo->bindParameter(':method', $method, PDO::PARAM_STR);
        $pdo->bindParameter(':access_at', $access_at, PDO::PARAM_STR);
        $pdo->execute();
        $pdo->commitTransaction();
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