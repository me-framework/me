<?php
namespace me\schema\conditions;
class SimpleConditionBuilder extends ConditionBuilder {
    /**
     * Method builds the raw SQL from the $expression that will not be additionally
     * escaped or quoted.
     *
     * @param \me\schema\conditions\SimpleCondition $condition the condition to be built.
     * @param array $params the binding parameters.
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build($condition, &$params) {
        $operator = $condition->getOperator();
        $column   = $this->queryBuilder->quote($condition->getColumn());
        $value    = $condition->getValue();

        if ($value === null) {
            return "$column IS NULL";
        }
        $phName = $this->queryBuilder->bindParam($value, $params);

        return "$column $operator $phName";
    }
}