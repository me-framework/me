<?php
namespace me\schema\pgsql;
use me\schema\Schema;
class PgsqlSchema extends Schema {
    public function getQueryBuilder() {
        if (is_null($this->_queryBuilder)) {
            $this->_queryBuilder = new PgsqlQueryBuilder();
        }
        return $this->_queryBuilder;
    }
    public function getTableSchema($table_name) {
        
    }
}