<?php
namespace me\schema\querybuilder;
trait BaseTrait {
    //
    public $quoteCharacter = '';
    public function quote($name) {
        if (strpos($name, '.') === false) {
            return $this->quoteSimple($name);
        }
        $parts = explode('.', $name);
        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimple($part);
        }
        return implode('.', $parts);
    }
    public function quoteSimple($name) {
        if (is_string($this->quoteCharacter)) {
            $startingCharacter = $endingCharacter   = $this->quoteCharacter;
        }
        else {
            list($startingCharacter, $endingCharacter) = $this->quoteCharacter;
        }
        return strpos($name, $startingCharacter) === false ? $startingCharacter . $name . $endingCharacter : $name;
    }
    //
    /**
     * @param array $columns Columns
     * @return string
     */
    public function buildSelect($columns) {
        if (empty($columns)) {
            return 'SELECT *';
        }
        foreach ($columns as $i => $column) {
            if (is_string($i) && $i !== $column) {
                if (strpos($column, '(') === false) {
                    $column = $this->quote($column);
                }
                $columns[$i] = "$column AS " . $this->quote($i);
            }
            elseif (strpos($column, '(') === false) {
                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $column, $matches)) {
                    $columns[$i] = $this->quote($matches[1]) . ' AS ' . $this->quote($matches[2]);
                }
                else {
                    $columns[$i] = $this->quote($column);
                }
            }
        }
        return 'SELECT ' . implode(', ', $columns);
    }
    //
    /**
     * @param array $tables Table Names
     * @return string
     */
    public function buildFrom($tables) {
        if (empty($tables)) {
            return '';
        }
        $tables = $this->quoteTableNames($tables);
        return 'FROM ' . implode(', ', $tables);
    }
    private function quoteTableNames($tables) {
        foreach ($tables as $i => $table) {
            if (is_string($i)) {
                if (strpos($table, '(') === false) {
                    $table = $this->quote($table);
                }
                $tables[$i] = "$table " . $this->quote($i);
            }
            elseif (strpos($table, '(') === false) {
                if ($tableWithAlias = $this->extractAlias($table)) {
                    $tables[$i] = $this->quote($tableWithAlias[1]) . ' ' . $this->quote($tableWithAlias[2]);
                }
                else {
                    $tables[$i] = $this->quote($table);
                }
            }
        }
        return $tables;
    }
    protected function extractAlias($table) {
        if (preg_match('/^(.*?)(?i:\s+as|)\s+([^ ]+)$/', $table, $matches)) {
            return $matches;
        }
        return false;
    }
    //
    /**
     * @param array $columns Columns
     * @return string
     */
    public function buildGroupBy($columns) {
        if (empty($columns)) {
            return '';
        }
        foreach ($columns as $i => $column) {
            if (strpos($column, '(') === false) {
                $columns[$i] = $this->quote($column);
            }
        }
        return 'GROUP BY ' . implode(', ', $columns);
    }
    /**
     * @param array $columns Columns
     * @return string
     */
    public function buildOrderBy($columns) {
        if (empty($columns)) {
            return '';
        }
        $orders = [];
        foreach ($columns as $name => $direction) {
            $orders[] = $this->quote($name) . ($direction === SORT_DESC ? ' DESC' : '');
        }
        return 'ORDER BY ' . implode(', ', $orders);
    }
    /**
     * @param integer $limit Limit
     * @param integer $offset Offset
     * @return string
     */
    public function buildLimit($limit, $offset) {
        $sql = '';
        if (ctype_digit((string) $limit)) {
            $sql = 'LIMIT ' . $limit;
        }
        if (ctype_digit((string) $offset) && (string) $offset !== '0') {
            $sql .= ' OFFSET ' . $offset;
        }
        return ltrim($sql);
    }
}