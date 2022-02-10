<?php
namespace me\schema\conditions;
class BetweenConditionBuilder extends ConditionBuilder {
    public function build($expression, &$params) {
        $operator = $expression->getOperator();
        $column   = $expression->getColumn();
        if (strpos($column, '(') === false) {
            $column = $this->queryBuilder->quote($column);
        }
        $phName1 = $this->queryBuilder->bindParam($expression->getIntervalStart(), $params);
        $phName2 = $this->queryBuilder->bindParam($expression->getIntervalEnd(), $params);
        return "$column $operator $phName1 AND $phName2";
    }
}