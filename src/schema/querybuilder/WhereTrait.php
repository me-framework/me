<?php
namespace me\schema\querybuilder;
use Exception;
use me\schema\conditions\Condition;
use me\schema\conditions\HashCondition;
trait WhereTrait {
    public $conditionClasses  = [
        'AND'         => 'me\schema\conditions\AndCondition',
        'OR'          => 'me\schema\conditions\OrCondition',
        'NOT'         => 'me\schema\conditions\NotCondition',
        'IN'          => 'me\schema\conditions\InCondition',
        'NOT IN'      => 'me\schema\conditions\InCondition',
        'LIKE'        => 'me\schema\conditions\LikeCondition',
        'NOT LIKE'    => 'me\schema\conditions\LikeCondition',
        'BETWEEN'     => 'me\schema\conditions\BetweenCondition',
        'NOT BETWEEN' => 'me\schema\conditions\BetweenCondition',
    ];
    public $conditionBuilders = [
        'me\schema\conditions\HashCondition'          => 'me\schema\conditions\HashConditionBuilder',
        'me\schema\conditions\SimpleCondition'        => 'me\schema\conditions\SimpleConditionBuilder',
        'me\schema\conditions\ConjunctionCondition'   => 'me\schema\conditions\ConjunctionConditionBuilder',
        'me\schema\conditions\AndCondition'           => 'me\schema\conditions\ConjunctionConditionBuilder',
        'me\schema\conditions\OrCondition'            => 'me\schema\conditions\ConjunctionConditionBuilder',
        'me\schema\conditions\NotCondition'           => 'me\schema\conditions\NotConditionBuilder',
        'me\schema\conditions\InCondition'            => 'me\schema\conditions\InConditionBuilder',
        'me\schema\conditions\LikeCondition'          => 'me\schema\conditions\LikeConditionBuilder',
        'me\schema\conditions\BetweenCondition'       => 'me\schema\conditions\BetweenConditionBuilder',
        'me\schema\conditions\BetweenColumnCondition' => 'me\schema\conditions\BetweenColumnConditionBuilder',
    ];
    /**
     * @param array|string|\me\schema\conditions\Condition $condition Conditions
     * @param array $params SQL Parameters
     * @return string
     */
    public function buildWhere($condition, &$params) {
        $where = $this->buildCondition($condition, $params);
        return $where === '' ? '' : 'WHERE ' . $where;
    }
    /**
     * @param array|string|\me\schema\conditions\Condition $condition Condition
     * @param array $params SQL Parameters
     * @return string Raw Condition
     */
    public function buildCondition($condition, &$params) {
        if (is_array($condition)) {
            if (empty($condition)) {
                return '';
            }
            $condition = $this->createConditionFromArray($condition);
        }
        if ($condition instanceof Condition) {
            $builder = $this->getConditionBuilder($condition);
            return $builder->build($condition, $params);
        }
        return (string) $condition;
    }
    /**
     * @param array $condition Condition Array
     * @return \me\schema\conditions\Condition Condition Object
     */
    public function createConditionFromArray($condition) {
        if (isset($condition[0])) {
            $operator  = strtoupper(array_shift($condition));
            /** @var \me\schema\conditions\Condition $className */
            $className = 'me\schema\conditions\SimpleCondition';
            if (isset($this->conditionClasses[$operator])) {
                $className = $this->conditionClasses[$operator];
            }
            return $className::fromArrayDefinition($operator, $condition);
        }
        return new HashCondition($condition);
    }
    /**
     * @param \me\schema\conditions\Condition $condition Condition
     * @return \me\schema\conditions\ConditionBuilder Condition Builder
     */
    public function getConditionBuilder($condition) {
        $className = get_class($condition);
        if (!isset($this->conditionBuilders[$className])) {
            throw new Exception('Condition of class ' . $className . ' can not be built in ' . get_class($this));
        }
        if (!is_object($this->conditionBuilders[$className])) {
            $this->conditionBuilders[$className] = new $this->conditionBuilders[$className]($this);
        }
        return $this->conditionBuilders[$className];
    }
}