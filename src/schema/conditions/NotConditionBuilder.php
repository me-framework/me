<?php
namespace me\schema\conditions;
class NotConditionBuilder extends ConditionBuilder {
    public function build($expression, &$params) {
        $operand = $expression->getCondition();
        if ($operand === '') {
            return '';
        }
        $expession = $this->queryBuilder->buildCondition($operand, $params);
        return "{$this->getNegationOperator()} ($expession)";
    }
    protected function getNegationOperator() {
        return 'NOT';
    }
}