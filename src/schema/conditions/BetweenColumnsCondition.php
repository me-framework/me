<?php
namespace me\schema\conditions;
use Exception;
class BetweenColumnsCondition extends Condition {
    private $operator;
    private $value;
    private $intervalStartColumn;
    private $intervalEndColumn;
    public function __construct($value, $operator, $intervalStartColumn, $intervalEndColumn) {
        parent::__construct();
        $this->value               = $value;
        $this->operator            = $operator;
        $this->intervalStartColumn = $intervalStartColumn;
        $this->intervalEndColumn   = $intervalEndColumn;
    }
    public function getOperator() {
        return $this->operator;
    }
    public function getValue() {
        return $this->value;
    }
    public function getIntervalStartColumn() {
        return $this->intervalStartColumn;
    }
    public function getIntervalEndColumn() {
        return $this->intervalEndColumn;
    }
    public static function fromArrayDefinition($operator, $operands) {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new Exception("Operator '$operator' requires three operands.");
        }
        return new static($operands[0], $operator, $operands[1], $operands[2]);
    }
}