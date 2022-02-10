<?php
namespace me\schema\conditions;
use me\helpers\ArrayHelper;
class HashConditionBuilder extends ConditionBuilder {
    /**
     * Method builds the raw SQL from the $condition that will not be additionally
     * escaped or quoted.
     *
     * @param \me\schema\conditions\HashCondition $condition the condition to be built.
     * @param array $params the binding parameters.
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build($condition, &$params) {
        $hash = $condition->getHash();

        $parts = [];
        foreach ($hash as $column => $value) {
            if (ArrayHelper::isTraversable($value)) {
                // IN condition
                $parts[] = $this->queryBuilder->buildCondition(new InCondition($column, 'IN', $value), $params);
                continue;
            }
            $column = $this->queryBuilder->quote($column);
            if ($value === null) {
                $parts[] = "$column IS NULL";
                continue;
            }
            $phName  = $this->queryBuilder->bindParam($value, $params);
            $parts[] = "$column=$phName";
        }

        return count($parts) === 1 ? $parts[0] : '(' . implode(') AND (', $parts) . ')';
    }
}