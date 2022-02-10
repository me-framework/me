<?php
namespace me\schema\conditions;
use Exception;
class InCondition extends Condition {
    private $operator;
    private $column;
    private $values;
    public function __construct($column, $operator, $values) {
        parent::__construct();
        $this->column   = $column;
        $this->operator = $operator;
        $this->values   = $values;
    }
    public function getOperator() {
        return $this->operator;
    }
    public function getColumn() {
        return $this->column;
    }
    public function getValues() {
        return $this->values;
    }
    /**
     * {@inheritdoc}
     * @throws Exception if wrong number of operands have been given.
     */
    public static function fromArrayDefinition($operator, $operands) {
        if (!isset($operands[0], $operands[1])) {
            throw new Exception("Operator '$operator' requires two operands.");
        }
        return new static($operands[0], $operator, $operands[1]);
    }
}