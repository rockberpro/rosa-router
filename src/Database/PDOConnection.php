<?php

namespace Rockberpro\RestRouter\Database;

use Rockberpro\RestRouter\Utils\DotEnv;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

class PDOConnection
{
    private static ?PDO $sharedPdo = null;

    private string $username;
    private string $password;
    private string $dbname;
    private string $hostname;
    private string $port;
    private string $driverType;

    private ?PDO $pdo = null;
    private ?PDOStatement $preparedStatement = null;
    private ?string $standardStatement = null;

    /**
     * @method __construct
     * @return void
     * @throws RuntimeException
     */
    public function __construct()
    {
        if (self::$sharedPdo !== null) {
            $this->pdo = self::$sharedPdo;
            return;
        }

        if ($this->loadConfigurations()) {
            $this->configurePDO();
            self::$sharedPdo = $this->pdo;
        }
        else {
            throw new RuntimeException("Error configuring PDO");
        }
    }

    /**
     * Load settings from .env file
     * 
     * @method loadConfigurations
     * @param string $databaseName
     * @return boolean : 'false' -> error, 'true' -> success
     */
    private function loadConfigurations()
    {
        $this->setUsername(DotEnv::get('API_DB_USER'));
        $this->setPassword(DotEnv::get('API_DB_PASS'));
        $this->setDbName(DotEnv::get('API_DB_NAME'));
        $this->setHostname(DotEnv::get('API_DB_HOST'));
        $this->setPort(DotEnv::get('API_DB_PORT'));
        $this->setDriverType(DotEnv::get('API_DB_TYPE'));

        return true;
    }

    /**
     * Setup PDO
     * 
     * @method configurePDO
     * @return void
     * @throws RuntimeException
     */
    private function configurePDO()
    {
        try {
            $this->setPdo(
                new PDO(
                    $this->getDsnUrl(),
                    $this->getUsername(),
                    $this->getPassword(),
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                )
            );
        }
        catch (Throwable $e) {
            throw new RuntimeException("Error establishing connection: {$e->getMessage()}");
        }
    }  

    /**
     * Builds DNS URL for PDO
     * 
     * @method getDsnUrl
     * @return string
     */
    private function getDsnUrl()
    {
        $driver_type = $this->getDriveType();
        $hostname = $this->getHostName();
        $post = $this->getPort();
        $dbname = $this->getDbName();

        return sprintf('%s:host=%s;port=%s;dbname=%s', $driver_type, $hostname, $post, $dbname);
    }

    /**
     * Creates a standard SQL ANSI statement
     * 
     * @method createStandardStatement
     * @param string $sqlQuery
     * @param array $options
     * @return void
     */
    public function createStandardStatement($statement)
    {
        $this->setStandardStatement($statement);
    }

    /**
     * Creates a prepared statement
     * 
     * @method preparedStatement
     * @param string $sqlQuery
     * @param array $options
     * @return void
     */
    public function createPreparedStatement($statement)
    {
        $this->setPreparedStatement($this->getPdo()->prepare($statement));
    }

    /**
     * Adds parpameters to the statement
     * * $pdo->bindParameter(':id', $value, PDO::PARAM_INT);
     * * $pdo->bindParameter(':id', $value, PDO::PARAM_STR);
     * 
     * @param string $column,
     * @param string $value
     * @param mixed $pdoParamType PDO::PARAM_STR
     * @return void
     * @throws RuntimeException
     */
    public function bindParameter($column, $value, $pdoParamType)
    {
        if (is_null($this->getPreparedStatement())) {
            throw new RuntimeException("Stantement was not initialized");
        }

        $this->getPreparedStatement()->bindParam($column, $value, $pdoParamType);
    }

    /**
     * Finds a single object via PDO
     * 
     * @method fetchOneByPreparedStatement
     * @return ?object
     */
    public function fetch($mode = PDO::FETCH_OBJ)
    {
        if ($this->getStandardStatement()) {
            return $this->getPdo()
                        ->query($this->getStandardStatement())
                        ->fetch($mode);
        }
        else if (
               !($this->getStandardStatement())
            && ($this->getPreparedStatement())
        ){
            if (!$this->getPreparedStatement()->execute()) {
                return null;    
            }

            return $this->getPreparedStatement()->fetch($mode);
        }

        return null;
    }

   /**
     * Finds multiple objects via PDO
     * 
     * @method fetchAllByPreparedStatement
     * @return ?array[object]
     */
    public function fetchAll($mode = PDO::FETCH_OBJ)
    {
        if ($this->getStandardStatement()) {
            return $this->getPdo()
                        ->query($this->getStandardStatement())
                        ->fetchAll($mode);
        }
        else if (
                !($this->getStandardStatement())
             && ($this->getPreparedStatement())
        ){
            if (!$this->getPreparedStatement()->execute()) {
                return null;
            }

            return $this->getPreparedStatement()->fetchAll($mode);
        }

        return null;
    }

    /**
     * Builds a query
     * 
     * @method getStatement
     * @param $paramValues
     */
    public function getStatement($paramValues = true)
    {
        if ($this->getStandardStatement()) {
            return $this->getStandardStatement();
        }
        else if (!($this->getStandardStatement())
             &&  ($this->getPreparedStatement())
        ){
            if (!$this->getPreparedStatement()->execute()) {
                return null;
            }
            if ($paramValues) {
                $this->getPreparedStatement()->execute();
                return $this->getPreparedStatement()->debugDumpParams();
            }
            else {
                return $this->getPreparedStatement();
            }
        }

        return null;
    }

    /**
     * Executes statement
     *
     * @method insert
     * @return boolean
     */
    public function execute()
    {
        if ($this->getStandardStatement()) {
            return $this->getPdo()->exec($this->getStandardStatement());
        }

        if ($this->getPreparedStatement()) {
            return $this->getPreparedStatement()
                        ->execute();
        }

        return false;
    }

    /**
     * Counts the number of rows
     * 
     * @method rowCount
     */
    public function rowCount()
    {
        if ($this->getStandardStatement()) {
            $rowCount = $this->getPdo()->prepare($this->getStandardStatement());
            $rowCount->execute();

            return $rowCount->rowCount();
        }

        if ($this->getPreparedStatement()) {
            $rowCount =  $this->getPreparedStatement();
            $rowCount->execute();

            return $rowCount->rowCount();
        }
    }

    /**
     * Starts a new transaction
     * 
     * @method beginTransaction
     * @return void
     */
    public function beginTransaction()
    {
        $this->getPdo()->beginTransaction();
    }

   /**
     * Commits current transaction
     * 
     * @method commitTransaction
     * @return void
     */
    public function commitTransaction()
    {
        $this->getPdo()->commit();
    }

    /**
     * Rollbacks current transaction
     * 
     * @method beginTransaction
     * @return void
     */
    public function rollbackTransaction()
    {
        $this->getPdo()->rollback();
    }

    /**
     * Closes the connection
     *
     * @method closeConnection
     * @return void
     */
    public function closeConnection()
    {
        $this->pdo = null;
        self::$sharedPdo = null;
    }

    /**
     * @method getDataBaseName
     * @return string
     */
    public function getDataBaseName()
    {
        return $this->getDbName();
    }

    /**
     * @method setStandardStatement
     * @param string $standardStatement
     */
    private function setStandardStatement($standardStatement)
    {
        $this->standardStatement = $standardStatement;
    }
    /**
     * @method getStandardStatement
     * @return string statement
     */
    private function getStandardStatement()
    {
        return $this->standardStatement;
    }

    /**
     * @method setPreparedStatement
     * @param ?PDOStatement preparedStatement
     */
    private function setPreparedStatement($preparedStatement)
    {
        $this->preparedStatement = $preparedStatement;
    }
    /**
     * @method getPreparedStatement
     * @return ?PDOStatement
     */
    private function getPreparedStatement()
    {
        return $this->preparedStatement;
    }
    /**
     * Getters & Setters
     */
    private function setPdo($pdo)
    {
        $this->pdo = $pdo;
    }
    public function getPdo() 
    {
        return $this->pdo;
    }
    private function setUsername($username)
    {
        $this->username = $username;
    }
    private function getUsername()
    {
        return $this->username;
    }
    private function setPassword($password)
    {
        $this->password = $password;
    }
    private function getPassword()
    {
        return $this->password;
    }
    private function setDbName($dbname)
    {
        $this->dbname = $dbname;
    }
    private function getDbName()
    {
        return $this->dbname;
    }
    private function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }
    private function getHostName()
    {
        return $this->hostname;
    }
    private function setPort($port)
    {
        $this->port = $port;
    }
    private function getPort()
    {
        return $this->port;
    }
    private function setDriverType($driverType)
    {
        $this->driverType = $driverType;
    }
    private function getDriveType()
    {
        return $this->driverType;
    }
}