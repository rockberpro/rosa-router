<?php

namespace Rockberpro\RestRouter\Database;

use Rockberpro\RestRouter\Utils\DotEnv;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;
use InvalidArgumentException;

/**
 * PDO Database connector
 * 
 * @author Samuel Oberger Rockenbach <samuel.rockenbach@univates.br>
 * @since july-2022
 */
class PDOConnection
{
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
        if($this->loadConfigurations())
        {
            $this->configurePDO();
        }
        else
        {
            throw new RuntimeException("Error configuring PDO");
        }
    }

    /**
     * Carregar configuracoes de arquivo INI
     * 
     * @since 1.0
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
     * Configura o DSN da PDO
     * 
     * @since 1.0
     * 
     * @method configurePDO
     * @return void
     * @throws RuntimeException
     */
    private function configurePDO()
    {
        try 
        {
            $this->setPdo(
                new PDO(
                    $this->getDsnUrl(),
                    $this->getUsername(),
                    $this->getPassword(),
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                )
            );
        }
        catch(Throwable $e)
        {
            throw new RuntimeException("Error establishing connection: {$e->getMessage()}");
        }
    }  

    /**
     * Builds DNS URL for PDO
     * 
     * @since 1.0
     * 
     * @method getDsnUrl
     * @return string
     */
    private function getDsnUrl()
    {
        return (
            "{$this->getDriveType()}:host={$this->getHostName()};port={$this->getPort()};dbname={$this->getDbName()}"
        );
    }

    /**
     * Creates a standard SQL ANSI statement
     * 
     * @since 1.0
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
     * @since 1.0
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
     * @since 1.0
     * 
     * @param string $column,
     * @param string $value
     * @param mixed $pdoParamType PDO::PARAM_STR
     * @return void
     * @throws RuntimeException
     */
    public function bindParameter($column, $value, $pdoParamType)
    {
        if(is_null($this->getPreparedStatement()))
        {
            throw new RuntimeException("Stantement was not initialized");
        }

        $this->getPreparedStatement()->bindParam($column, $value, $pdoParamType);
    }

    /**
     * Finds a single object via PDO
     * 
     * @since 1.0
     * 
     * @method fetchOneByPreparedStatement
     * @return ?object
     */
    public function fetch($mode = PDO::FETCH_OBJ)
    {
        if($this->getStandardStatement())
        {
            return $this->getPdo()
                        ->query($this->getStandardStatement())
                        ->fetch($mode);
        }
        else if(!($this->getStandardStatement())
             &&  ($this->getPreparedStatement())
        )
        {
            if(!$this->getPreparedStatement()->execute())
            {
                return null;    
            }
            return $this->getPreparedStatement()->fetch($mode);
        }
        return null;
    }

   /**
     * Finds multiple objects via PDO
     * 
     * @since 1.0
     * 
     * @method fetchAllByPreparedStatement
     * @return ?array[object]
     */
    public function fetchAll($mode = PDO::FETCH_OBJ)
    {
        if($this->getStandardStatement())
        {
            return $this->getPdo()
                        ->query($this->getStandardStatement())
                        ->fetchAll($mode);
        }
        else if(!($this->getStandardStatement())
             &&  ($this->getPreparedStatement())
        )
        {
            if(!$this->getPreparedStatement()->execute())
            {
                return null;
            }
            return $this->getPreparedStatement()->fetchAll($mode);
        }
    }

    /**
     * Builds a query
     * 
     * @since 1.0
     * 
     * @method getStatement
     * @param $paramValues valores parametrizados
     * @return string
     */
    public function getStatement($paramValues = true)
    {
        if($this->getStandardStatement())
        {
            return $this->getStandardStatement();
        }
        else if(!($this->getStandardStatement())
             &&  ($this->getPreparedStatement())
        )
        {
            if(!$this->getPreparedStatement()->execute())
            {
                return null;
            }
            if($paramValues)
            {
                $this->getPreparedStatement()->execute();
                return $this->getPreparedStatement()->debugDumpParams();
            }
            else
            {
                return $this->getPreparedStatement();
            }
        }
    }

    /**
     * Executes statement
     *
     * @since 1.0
     * 
     * @method insert
     * @return boolean
     */
    public function execute()
    {
        if($this->getStandardStatement())
        {
            return $this->getPdo()->exec($this->getStandardStatement());
        }

        if($this->getPreparedStatement())
        {
            return $this->getPreparedStatement()
                        ->execute();
        }

        return false;
    }

    /**
     * Counts the number of rows
     * 
     * @since 1.1
     * 
     * @method rowCount
     */
    public function rowCount()
    {
        if($this->getStandardStatement())
        {
            $rowCount = $this->getPdo()->prepare($this->getStandardStatement());
            $rowCount->execute();

            return $rowCount->rowCount();
        }

        if($this->getPreparedStatement())
        {
            $rowCount =  $this->getPreparedStatement();
            $rowCount->execute();

            return $rowCount->rowCount();
        }
    }

    /**
     * Starts a new transaction
     * 
     * @since 1.1
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
     * @since 1.1
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
     * @since 1.1
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
     * @since 1.1
     * 
     * @method closeConnection
     * @return void
     */
    public function closeConnection() 
    {
        $this->pdo = null;
    }

    /**
     * Fetches a single object
     * 
     * @since 1.0
     * 
     * @method fetchObject
     * @param object $object
     * @param string $entity : nome da entidade-tabela
     * @return object
     */
    public function fetchObject($object, $entity, $mode = PDO::FETCH_OBJ)
    {
        return $this->fetchGenericObject($object, $entity)
                    ->fetch($mode);
    }

    /**
     * Fetches multiple objects
     * 
     * @since 1.0
     * 
     * @method fetchObject
     * @param object $object
     * @param string $entity nome da entidade-tabela
     * @param mixed $offset 
     * @param mixed $limit
     * @param string $mode
     * @return object
     */
    public function fetchAllObjects(
        $object, $entity, $orderKey = null, $order = 'desc', $offset = null, $limit = null, $mode = PDO::FETCH_OBJ
    )
    {
        return $this->fetchGenericObject(
            $object, $entity, $orderKey, $order, $limit, $offset
        )->fetchAll($mode);
    }

    /**
     * Generic fetch method
     * 
     * @since 1.0
     * 
     * @method fetchAllObjects
     * @param object $object
     * @param string $entity nome da entidade-tabela
     * @return object PDOStatement
     */
    private function fetchGenericObject(
        $object, $entity, $orderKey = null, $order = 'desc', $limit = null, $offset = null
    )
    {
        $constraints = get_object_vars($object);

        if(!empty($constraints)) {
            $statement = ("SELECT * FROM {$entity} WHERE ");
        }
        else {
            $statement = ("SELECT * FROM {$entity}");
        }

        if(!empty($constraints)) {
            foreach(array_keys($constraints) as $index)
            {
                $statement .= "{$index} {$constraints[$index][0]} :{$index} AND ";
            }
            $statement = substr($statement, 0, -5);
        }

        if(isset($orderKey)) {
            $statement .= " ORDER BY {$orderKey} {$order}";
        }
        if(isset($limit)) {
            $statement .= " LIMIT {$limit}";
        }
        if(isset($offset)) {
            $statement .= " OFFSET {$offset}";
        }

        $binds = [];
        foreach($constraints as $index => $bind)
        {
            $binds[$index] = $bind[1];
        }

        $pdo =  $this->getPdo();
        $stmt = $pdo->prepare($statement);
        $stmt->execute($binds);

        return $stmt;
    }

    /**
     * Stores an object
     * 
     * @since 1.0
     * 
     * @method store
     * @param object $object
     * @param string $entity nome da entidade-tabela
     * @return boolean
     */
    public function insertObject($object, $entity)
    {
        $binds = get_object_vars($object);
        $columns = implode(',', array_keys($binds));
        $params = ':' . implode(',:', array_keys($binds));

        $statement = "INSERT INTO {$entity} ({$columns}) VALUES ({$params})";

        return $this->getPdo()
                    ->prepare($statement)
                    ->execute($binds);
    }

    /**
     * Updates an object
     * 
     * @since 1.0
     * 
     * @method updateObject
     * @param object $object
     * @param string $entity  nome da entidade-tabela
     * @param string $primaryKey
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function updateObject($object, $entity, $primaryKey = 'id')
    {
        if(!isset($object->{$primaryKey})) throw new InvalidArgumentException();

        $binds = get_object_vars($object);

        $statement = ("UPDATE {$entity} SET ");
        foreach(array_keys($binds) as $index)
        {
            $statement .= "{$index} = :{$index},";
        }
        $statement = substr($statement, 0, -1);
        $statement .= (" WHERE {$primaryKey} = :{$primaryKey}");

        array_walk($binds, function($value, $key) use (&$binds)
        {
            if(gettype($value) === 'boolean') {
                $binds[$key] = (int) $value;
            }
        });

        return $this->getPdo()
                    ->prepare($statement)
                    ->execute($binds);
    }

    /**
     * Deletes an object
     * 
     * @since 1.0
     * 
     * @method deleteObject
     * @param object $object
     * @param string $entity : nome da entidade-tabela
     * @throws InvalidArgumentException
     * 
     */
    public function deleteObject($object, $entity, $primaryKey = 'id')
    {
        if(!isset($object->{$primaryKey})) throw new InvalidArgumentException();

        $binds["{$primaryKey}"] = $object->{$primaryKey};
        $statement = "DELETE FROM {$entity} WHERE $primaryKey = :{$primaryKey}";

        return $this->getPdo()
                    ->prepare($statement)
                    ->execute($binds);
    }

    /**
     * Verifies if an object exists
     * 
     * @since 1.0
     * 
     * @method objectExists
     * @param object $object
     * @param string $entity
     * @param string $primaryKey
     * @throws InvalidArgumentException
     * @return boolean
     */
    public function existsObject($object, $entity, $primaryKey = 'id')
    {
        if(!isset($object->{$primaryKey})) throw new InvalidArgumentException('Object has not a primary-key!');

        $this->createPreparedStatement(
            "SELECT count(*) FROM {$entity} WHERE {$primaryKey} = :{$primaryKey}"
        );
        $this->bindParameter("{$primaryKey}", (string) $object->{$primaryKey}, PDO::PARAM_STR);

        return (bool) $this->fetch()->count;
    }

    /**
     * Inserts either updates an object
     *
     * @since 1.0
     * 
     * @method insertUpdate
     * @param string $entity
     * @param boolean $autoIncrement
     * @param string $primaryKey
     * @return integer
     */
    public function insertUpdate(
        $object,
        $entity,
        $autoIncrement = true,
        $primaryKey = 'id'
    )
    {
        if (!isset($object->{$primaryKey}))
        {
            if (!$autoIncrement)
            {
                $nextId = $this->getPdo()
                               ->query("SELECT max({$primaryKey}::int)+1 as {$primaryKey} FROM {$entity}")
                               ->fetch(PDO::FETCH_OBJ);
                $object->{$primaryKey} = $nextId->{$primaryKey} ?? 1;
            }
            $this->insertObject($object, $entity);
            return $object->{$primaryKey};
        }
        elseif (
            isset($object->{$primaryKey})
            && !$this->existsObject($object, $entity, $primaryKey)
        )
        {
            $this->insertObject($object, $entity);
            return $object->{$primaryKey};
        }
        elseif (
            isset($object->{$primaryKey})
            && $this->existsObject($object, $entity, $primaryKey)
        )
        {
            $this->updateObject($object, $entity, $primaryKey);
            return $object->{$primaryKey};
        }

        return null;
    }

    /**
     * Get last id
     * 
     * @since 1.1
     * 
     * @method maxId
     * @param string entity nome da entidade-tabela
     * @param string $primaryKey
     * @return integer
     */
    public function lastId($entity, $primaryKey = 'id')
    {
        $this->createStandardStatement(
            "SELECT max({$primaryKey}::int) as {$primaryKey} FROM {$entity}"
        );
        return $this->fetch()->{$primaryKey};
    }

    /**
     * Get next id
     * 
     * @since 1.1
     * 
     * @method maxId
     * @param string $entity nome da entidade-tabela
     * @param string $primaryKey
     * @return $object
     */
    public function nextId($entity, $primaryKey = 'id')
    {
        $this->createStandardStatement(
            "SELECT max({$primaryKey}::int)+1 as {$primaryKey} FROM {$entity}"
        );
        return $this->fetch()->{$primaryKey};
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