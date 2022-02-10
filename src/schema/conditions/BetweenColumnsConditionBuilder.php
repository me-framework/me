<?php
namespace me\schema\conditions;
class BetweenColumnsConditionBuilder extends ConditionBuilder {
    public function build($expression, &$params) {
        $operator = $expression->getOperator();
        $startColumn = $this->escapeColumnName($expression->getIntervalStartColumn());
        $endColumn   = $this->escapeColumnName($expression->getIntervalEndColumn());
        $value       = $this->queryBuilder->bindParam($expression->getValue(), $params);
        return "$value $operator $startColumn AND $endColumn";
    }
    protected function escapeColumnName($columnName) {
        if (strpos($columnName, '(') === false) {
            return $this->queryBuilder->quote($columnName);
        }
        return $columnName;
    }
}