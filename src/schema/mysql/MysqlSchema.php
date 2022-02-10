<?php
namespace me\schema\mysql;
use PDO;
use me\schema\Schema;
use me\schema\TableSchema;
use me\schema\ColumnSchema;
class MysqlSchema extends Schema {
    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     */
    public $typeMap = [
        'tinyint'    => self::TYPE_TINYINT,
        'bit'        => self::TYPE_INTEGER,
        'smallint'   => self::TYPE_SMALLINT,
        'mediumint'  => self::TYPE_INTEGER,
        'int'        => self::TYPE_INTEGER,
        'integer'    => self::TYPE_INTEGER,
        'bigint'     => self::TYPE_BIGINT,
        'float'      => self::TYPE_FLOAT,
        'double'     => self::TYPE_DOUBLE,
        'real'       => self::TYPE_FLOAT,
        'decimal'    => self::TYPE_DECIMAL,
        'numeric'    => self::TYPE_DECIMAL,
        'tinytext'   => self::TYPE_TEXT,
        'mediumtext' => self::TYPE_TEXT,
        'longtext'   => self::TYPE_TEXT,
        'longblob'   => self::TYPE_BINARY,
        'blob'       => self::TYPE_BINARY,
        'text'       => self::TYPE_TEXT,
        'varchar'    => self::TYPE_STRING,
        'string'     => self::TYPE_STRING,
        'char'       => self::TYPE_CHAR,
        'datetime'   => self::TYPE_DATETIME,
        'year'       => self::TYPE_DATE,
        'date'       => self::TYPE_DATE,
        'time'       => self::TYPE_TIME,
        'timestamp'  => self::TYPE_TIMESTAMP,
        'enum'       => self::TYPE_STRING,
        'varbinary'  => self::TYPE_BINARY,
        'json'       => self::TYPE_JSON,
    ];
    /**
     * 
     */
    public function getQueryBuilder() {
        if (is_null($this->_queryBuilder)) {
            $this->_queryBuilder = new MysqlQueryBuilder();
        }
        return $this->_queryBuilder;
    }
    /**
     * 
     */
    public function getTableSchema($table_name) {
        if (!isset($this->_tableSchema[$table_name])) {
            $this->_tableSchema[$table_name] = $this->loadTableSchema($table_name);
        }
        return $this->_tableSchema[$table_name];
    }
    /**
     * 
     */
    protected function loadTableSchema($name) {
        $table = new TableSchema();
        $this->getQueryBuilder()->resolveTableNames($table, $name);
        $this->findColumns($table);
        $this->findConstraints($table);
        return $table;
    }
    /**
     * @param \me\schema\TableSchema $table Table Schema
     */
    protected function findColumns($table) {
        [$sql, $params] = $this->getQueryBuilder()->findColumns($table);
        $columns = $this->connection->getCommand()->fetchAll($sql, $params);
        foreach ($columns as $info) {
            if ($this->connection->pdo->getAttribute(PDO::ATTR_CASE) !== PDO::CASE_LOWER) {
                $info = array_change_key_case($info, CASE_LOWER);
            }
            $column = $this->loadColumnSchema($info);

            $table->columns[$column->name] = $column;
            if (!$column->isPrimaryKey) {
                continue;
            }
            $table->primaryKey[] = $column->name;
            if ($column->autoIncrement) {
                $table->sequenceName = '';
            }
        }
    }
    /**
     * @param \me\schema\TableSchema $table Table Schema
     */
    protected function findConstraints($table) {
        
    }
    /**
     * @param array $info Column Info
     * @return \me\schema\ColumnSchema Column Schema
     */
    protected function loadColumnSchema($info) {
        $column                = new ColumnSchema();
        $column->name          = $info['field'];
        $column->allowNull     = $info['null'] === 'YES';
        $column->isPrimaryKey  = strpos($info['key'], 'PRI') !== false;
        $column->autoIncrement = stripos($info['extra'], 'auto_increment') !== false;
        $column->comment       = $info['comment'];
        $column->dbType        = $info['type'];
        $column->unsigned      = stripos($column->dbType, 'unsigned') !== false;
        $column->type          = self::TYPE_STRING;
        if (preg_match('/^(\w+)(?:\(([^\)]+)\))?/', $column->dbType, $matches)) {
            $type = strtolower($matches[1]);
            if (isset($this->typeMap[$type])) {
                $column->type = $this->typeMap[$type];
            }
            if (!empty($matches[2])) {
                if ($type === 'enum') {
                    preg_match_all("/'[^']*'/", $matches[2], $values);
                    foreach ($values[0] as $i => $value) {
                        $values[$i] = trim($value, "'");
                    }
                    $column->enumValues = $values;
                }
                else {
                    $values            = explode(',', $matches[2]);
                    $column->size      = $column->precision = (int) $values[0];
                    if (isset($values[1])) {
                        $column->scale = (int) $values[1];
                    }
                    if ($column->size === 1 && $type === 'bit') {
                        $column->type = 'boolean';
                    }
                    elseif ($type === 'bit') {
                        if ($column->size > 32) {
                            $column->type = 'bigint';
                        }
                        elseif ($column->size === 32) {
                            $column->type = 'integer';
                        }
                    }
                }
            }
        }
        $column->phpType = $this->getColumnPhpType($column);
        if (!$column->isPrimaryKey) {
            /**
             * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed
             * as CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
             *
             * See details here: https://mariadb.com/kb/en/library/now/#description
             */
            if (($column->type === 'timestamp' || $column->type === 'datetime') && preg_match('/^current_timestamp(?:\(([0-9]*)\))?$/i', $info['default'], $matches)) {
                $column->defaultValue = new Expression('CURRENT_TIMESTAMP' . (!empty($matches[1]) ? '(' . $matches[1] . ')' : ''));
            }
            elseif (isset($type) && $type === 'bit') {
                $column->defaultValue = bindec(trim($info['default'], 'b\''));
            }
            else {
                $column->defaultValue = $column->phpTypecast($info['default']);
            }
        }
        return $column;
    }
}