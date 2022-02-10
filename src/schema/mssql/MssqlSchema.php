<?php
namespace me\schema\mssql;
use me\schema\Schema;
class MssqlSchema extends Schema {
    public function getQueryBuilder() {
        if (is_null($this->_queryBuilder)) {
            $this->_queryBuilder = new MssqlQueryBuilder();
        }
        return $this->_queryBuilder;
    }
    public function getTableSchema($table_name) {
        
    }
}