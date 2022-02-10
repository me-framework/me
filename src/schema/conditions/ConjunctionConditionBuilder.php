<?php
namespace me\schema\conditions;
class ConjunctionConditionBuilder extends ConditionBuilder {
    /**
     * @param \me\schema\conditions\ConjunctionCondition $condition
     * @param array $params
     */
    public function build($condition, &$params) {
        $parts = $this->buildExpressionsFrom($condition, $params);
        if (empty($parts)) {
            return '';
        }
        if (count($parts) === 1) {
            return reset($parts);
        }
        return '(' . implode(") {$condition->getOperator()} (", $parts) . ')';
    }
    /**
     * @param \me\schema\conditions\ConjunctionCondition $condition1
     * @param array $params
     */
    private function buildExpressionsFrom($condition1, &$params = []) {
        $parts = [];
        foreach ($condition1->getExpressions() as $condition) {
            if (is_array($condition)) {
                $condition = $this->queryBuilder->buildCondition($condition, $params);
            }
            if ($condition !== '') {
                $parts[] = $condition;
            }
        }
        return $parts;
    }
}