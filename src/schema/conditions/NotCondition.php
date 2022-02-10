<?php
namespace me\schema\conditions;
use Exception;
class NotCondition extends Condition {
    private $condition;
    public function __construct($condition) {
        parent::__construct();
        $this->condition = $condition;
    }
    public function getCondition() {
        return $this->condition;
    }
    public static function fromArrayDefinition($operator, $operands) {
        if (count($operands) !== 1) {
            throw new Exception("Operator '$operator' requires exactly one operand.");
        }
        return new static(array_shift($operands));
    }
}