<?php
namespace me\schema\conditions;
use me\core\Component;
abstract class ConditionBuilder extends Component {
    /**
     * @var \me\schema\QueryBuilder Query Builder
     */
    public $queryBuilder;
    /**
     * 
     */
    public function __construct($queryBuilder, $config = []) {
        parent::__construct($config);
        $this->queryBuilder = $queryBuilder;
    }
    /**
     * @param \me\schema\Condition $condition Condition
     * @param array $params SQL Parameters
     */
    abstract public function build($condition, &$params);
}