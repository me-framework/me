<?php
namespace me\schema;
use Me;
use Exception;
use me\core\Component;
use me\core\components\Container;
use me\schema\mysql\MysqlSchema;
use me\schema\pgsql\PgsqlSchema;
use me\schema\mssql\MssqlSchema;
class SchemaManager extends Component {
    /**
     * @var \me\database\Schema[]
     */
    private $_schema  = [];
    /**
     * @var array
     */
    public $schemaMap = [
        'mysql'  => MysqlSchema::class, // MySQL
        'pgsql'  => PgsqlSchema::class, // PostgreSQL
        'mssql'  => MssqlSchema::class, // older MSSQL driver on MS Windows hosts
        'sqlsrv' => MssqlSchema::class, // newer MSSQL driver on MS Windows hosts
    ];
    /**
     * @param string $connection Connection Name
     * @return \me\database\Connection Connection
     */
    public function getConnection($connection = null) {
        /* @var $database \me\database\DatabaseManager */
        $database = Me::$app->get('database');
        return $database->getConnection($connection);
    }
    /**
     * @param string $connection Connection Name
     * @return \me\database\Command Command
     */
    public function getCommand($connection = null) {
        /* @var $database \me\database\DatabaseManager */
        $database = Me::$app->get('database');
        return $database->getCommand($connection);
    }
    /**
     * @param string $connection Connection Name
     * @return \me\schema\Schema Schema
     */
    public function getSchema($connection = null) {
        if (!isset($this->_schema[$connection])) {
            $conn = $this->getConnection($connection);
            if (!isset($this->schemaMap[$conn->driver])) {
                throw new Exception("Schema { <b>$this->driver</b> } Not Found");
            }
            $this->_schema[$connection] = Container::build([
                        'class'      => $this->schemaMap[$conn->driver],
                        'connection' => $conn
            ]);
        }
        return $this->_schema[$connection];
    }
    /**
     * @param string $connection Connection Name
     * @return \me\schema\QueryBuilder Query Builder
     */
    public function getQueryBuilder($connection) {
        return $this->getSchema($connection)->getQueryBuilder();
    }
    /**
     * @param string $table_name Table Name
     * @param string $connection Connection Name
     * @return \me\schema\TableSchema Table Schema
     */
    public function getTableSchema($table_name, $connection = null) {
        return $this->getSchema($connection)->getTableSchema($table_name);
    }
}