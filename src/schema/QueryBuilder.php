<?php
namespace me\schema;
use me\core\Component;
abstract class QueryBuilder extends Component {
    use querybuilder\BaseTrait;
    use querybuilder\WhereTrait;
    public $separator    = ' ';
    public $param_prefix = ':me';
    /**
     * @param \me\schema\TableSchema $table Table Schema
     * @param string $name Table Name
     */
    abstract public function resolveTableNames($table, $name);
    /**
     * @param \me\schema\TableSchema $table Table Schema
     * @return array [string $sql, array $params]
     */
    abstract public function findColumns($table);
    /**
     * @param \me\schema\Query $query Query
     * @return array [string $sql, array $params]
     */
    public function build($query) {

        $query->prepare($this);

        $params  = [];
        $clauses = [
            $this->buildSelect($query->select),
            $this->buildFrom($query->from),
            $this->buildWhere($query->where, $params),
            $this->buildGroupBy($query->groupBy),
            $this->buildOrderBy($query->orderBy),
            $this->buildLimit($query->limit, $query->offset),
        ];

        $sql = implode($this->separator, array_filter($clauses));

        return [$sql, $params];
    }
    /**
     * @param mixed $value Value
     * @param array $params SQL Parameters
     * @return string phName
     */
    public function bindParam($value, &$params) {
        $phName          = $this->param_prefix . count($params);
        $params[$phName] = $value;
        return $phName;
    }
}