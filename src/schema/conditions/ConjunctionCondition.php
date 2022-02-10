<?php
namespace me\schema\conditions;
abstract class ConjunctionCondition extends Condition {
    protected $expressions;
    public function __construct($expressions) {
        parent::__construct();
        $this->expressions = $expressions;
    }
    public function getExpressions() {
        return $this->expressions;
    }
    abstract public function getOperator();
    /**
     * {@inheritdoc}
     */
    public static function fromArrayDefinition($operator, $operands) {
        return new static($operands);
    }
}