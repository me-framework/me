<?php
namespace me\schema\conditions;
use Exception;
class BetweenCondition extends Condition {
    private $operator;
    private $column;
    private $intervalStart;
    private $intervalEnd;
    public function __construct($column, $operator, $intervalStart, $intervalEnd) {
        parent::__construct();
        $this->column        = $column;
        $this->operator      = $operator;
        $this->intervalStart = $intervalStart;
        $this->intervalEnd   = $intervalEnd;
    }
    public function getOperator() {
        return $this->operator;
    }
    public function getColumn() {
        return $this->column;
    }
    public function getIntervalStart() {
        return $this->intervalStart;
    }
    public function getIntervalEnd() {
        return $this->intervalEnd;
    }
    /**
     * {@inheritdoc}
     * @throws Exception if wrong number of operands have been given.
     */
    public static function fromArrayDefinition($operator, $operands) {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new Exception("Operator '$operator' requires three operands.");
        }
        return new static($operands[0], $operator, $operands[1], $operands[2]);
    }
}