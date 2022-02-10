<?php
namespace me\schema\pgsql;
use me\schema\QueryBuilder;
class PgsqlQueryBuilder extends QueryBuilder {
    public $quoteCharacter = '"';
    public function resolveTableNames($table, $name) {
        
    }
    public function findColumns($table) {
        
    }
}