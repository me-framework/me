<?php
namespace me\schema\conditions;
use Exception;
class LikeCondition extends SimpleCondition {
    protected $escapingReplacements;
    public function setEscapingReplacements($escapingReplacements) {
        $this->escapingReplacements = $escapingReplacements;
    }
    public function getEscapingReplacements() {
        return $this->escapingReplacements;
    }
    public static function fromArrayDefinition($operator, $operands) {
        if (!isset($operands[0], $operands[1])) {
            throw new Exception("Operator '$operator' requires two operands.");
        }
        $condition = new static($operands[0], $operator, $operands[1]);
        if (isset($operands[2])) {
            $condition->escapingReplacements = $operands[2];
        }
        return $condition;
    }
}