<?php
namespace me\schema\conditions;
use Exception;
class LikeConditionBuilder extends ConditionBuilder {
    protected $escapingReplacements = [
        '%'  => '\%',
        '_'  => '\_',
        '\\' => '\\\\',
    ];
    protected $escapeCharacter;
    /**
     * @param \me\schema\conditions\LikeCondition $expression
     * @param array $params the binding parameters.
     */
    public function build($expression, &$params) {
        $operator = strtoupper($expression->getOperator());
        $column   = $expression->getColumn();
        $values   = $expression->getValue();
        $escape   = $expression->getEscapingReplacements();
        if ($escape === null || $escape === []) {
            $escape = $this->escapingReplacements;
        }
        list($andor, $not, $operator) = $this->parseOperator($operator);
        if (!is_array($values)) {
            $values = [$values];
        }
        if (empty($values)) {
            return $not ? '' : '0=1';
        }
        if (is_string($column) && strpos($column, '(') === false) {
            $column = $this->queryBuilder->quote($column);
        }
        $escapeSql = $this->getEscapeSql();
        $parts     = [];
        foreach ($values as $value) {
            $phName  = $this->queryBuilder->bindParam(empty($escape) ? $value : ('%' . strtr($value, $escape) . '%'), $params);
            $parts[] = "{$column} {$operator} {$phName}{$escapeSql}";
        }
        return implode($andor, $parts);
    }
    private function getEscapeSql() {
        if ($this->escapeCharacter !== null) {
            return " ESCAPE '{$this->escapeCharacter}'";
        }
        return '';
    }
    protected function parseOperator($operator) {
        if (!preg_match('/^(AND |OR |)(((NOT |))I?LIKE)/', $operator, $matches)) {
            throw new Exception("Invalid operator '$operator'.");
        }
        $andor    = ' ' . (!empty($matches[1]) ? $matches[1] : 'AND ');
        $not      = !empty($matches[3]);
        $operator = $matches[2];
        return [$andor, $not, $operator];
    }
}