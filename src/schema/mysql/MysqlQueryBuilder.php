<?php
namespace me\schema\mysql;
use me\schema\QueryBuilder;
class MysqlQueryBuilder extends QueryBuilder {
    public $quoteCharacter  = '`';
    /**
     * @param \me\schema\TableSchema $table Table Schema
     * @param string $name Table Name
     */
    public function resolveTableNames($table, $name) {
        $parts = explode('.', str_replace($this->quoteCharacter, '', $name));
        if (isset($parts[1])) {
            $table->schemaName = $parts[0];
            $table->name       = $parts[1];
            $table->fullName   = $table->schemaName . '.' . $table->name;
        }
        else {
            $table->fullName = $table->name     = $parts[0];
        }
    }
    /**
     * @param \me\schema\TableSchema $table Table Schema
     * @return array [string $sql, array $params]
     */
    public function findColumns($table) {
        return ['SHOW FULL COLUMNS FROM ' . $this->quote($table->fullName), []];
    }
}