<?php
namespace me\schema\mssql;
use me\schema\QueryBuilder;
class MssqlQueryBuilder extends QueryBuilder {
    public $quoteCharacter  = ['[', ']'];
    public function resolveTableNames($table, $name) {
        
    }
    public function findColumns($table) {
        
    }
}