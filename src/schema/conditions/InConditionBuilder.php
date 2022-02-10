<?php
namespace me\schema\conditions;
class InConditionBuilder extends ConditionBuilder {
    /**
     * @param \me\schema\conditions\InCondition $expression
     * @param array $params
     */
    public function build($expression, &$params) {
        $operator = strtoupper($expression->getOperator());
        $column   = $expression->getColumn();
        $values   = $expression->getValues();
        if ($column === []) {
            return $operator === 'IN' ? '0=1' : '';
        }
        if (!is_array($values) && !$values instanceof \Traversable) {
            // ensure values is an array
            $values = (array) $values;
        }
        if (is_array($values)) {
            $rawValues = $values;
        }
        elseif ($values instanceof \Traversable) {
            $rawValues = $this->getRawValuesFromTraversableObject($values);
        }
        $nullCondition         = null;
        $nullConditionOperator = null;
        if (isset($rawValues) && in_array(null, $rawValues, true)) {
            $nullCondition         = $this->getNullCondition($operator, $column);
            $nullConditionOperator = $operator === 'IN' ? 'OR' : 'AND';
        }
        $sqlValues = $this->buildValues($expression, $values, $params);
        if (empty($sqlValues)) {
            if ($nullCondition === null) {
                return $operator === 'IN' ? '0=1' : '';
            }
            return $nullCondition;
        }
        if (strpos($column, '(') === false) {
            $column = $this->queryBuilder->quote($column);
        }
        if (count($sqlValues) > 1) {
            $sql = "$column $operator (" . implode(', ', $sqlValues) . ')';
        }
        else {
            $operator = $operator === 'IN' ? '=' : '<>';
            $sql      = $column . $operator . reset($sqlValues);
        }
        return $nullCondition !== null && $nullConditionOperator !== null ? sprintf('%s %s %s', $sql, $nullConditionOperator, $nullCondition) : $sql;
    }
    protected function buildValues($condition, $values, &$params) {
        $sqlValues = [];
        $column    = $condition->getColumn();
        if (is_array($column)) {
            $column = reset($column);
        }
        if ($column instanceof \Traversable) {
            $column->rewind();
            $column = $column->current();
        }
        foreach ($values as $i => $value) {
            if (is_array($value) || $value instanceof \ArrayAccess) {
                $value = isset($value[$column]) ? $value[$column] : null;
            }
            if ($value === null) {
                continue;
            }
            $sqlValues[$i] = $this->queryBuilder->bindParam($value, $params);
        }
        return $sqlValues;
    }
    protected function getNullCondition($operator, $column) {
        $column = $this->queryBuilder->quote($column);
        if ($operator === 'IN') {
            return sprintf('%s IS NULL', $column);
        }
        return sprintf('%s IS NOT NULL', $column);
    }
    protected function getRawValuesFromTraversableObject($traversableObject) {
        $rawValues = [];
        foreach ($traversableObject as $value) {
            if (is_array($value)) {
                $values    = array_values($value);
                $rawValues = array_merge($rawValues, $values);
            }
            else {
                $rawValues[] = $value;
            }
        }
        return $rawValues;
    }
}