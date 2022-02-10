<?php
namespace me\schema;
use Me;
use me\core\Component;
class Query extends Component {
    use query\BaseTrait;
    use query\WhereTrait;
    use query\IndexByAsArray;
    /**
     * @var string Connection Name
     */
    public $connection;
    /**
     * @var string Class Name
     */
    public $modelClass;
    /**
     * @return \me\database\Command Command
     */
    public function getCommand() {
        /* @var $schema \me\schema\SchemaManager */
        $schema = Me::$app->get('schema');
        return $schema->getCommand($this->connection);
    }
    /**
     * @return \me\schema\QueryBuilder Query Builder
     */
    public function getQueryBuilder() {
        /* @var $schema \me\schema\SchemaManager */
        $schema = Me::$app->get('schema');
        return $schema->getQueryBuilder($this->connection);
    }
    /**
     * @param \me\schema\QueryBuilder $builder Query Builder
     */
    public function prepare($builder) {
        if ($this->from === null) {
            /* @var $modelClass \me\schema\Record */
            $modelClass = new $this->modelClass;
            $this->from = [$modelClass::tableName()];
        }
    }
    /**
     * @return array|\me\schema\Record[] rows | models
     */
    public function all() {

        [$sql, $params] = $this->getQueryBuilder()->build($this);
        $rows = $this->getCommand()->fetchAll($sql, $params);

        if ($rows === false || empty($rows)) {
            return [];
        }

        if ($this->asArray) {
            return $rows;
        }

        $models = [];
        foreach ($rows as $row) {
            /* @var $modelClass \me\schema\Record */
            $modelClass = new $this->modelClass;
            $modelClass->populate($row);
            if ($this->indexBy === null) {
                $models[] = $modelClass;
            }
            else {
                $key          = $row[$this->indexBy];
                $models[$key] = $modelClass;
            }
        }
        return $models;
    }
    /**
     * @return array|\me\schema\Record row | model
     */
    public function one() {

        [$sql, $params] = $this->getQueryBuilder()->build($this);
        $row = $this->getCommand()->fetchOne($sql, $params);

        if ($row === false) {
            return null;
        }

        if ($this->asArray) {
            return $row;
        }

        /* @var $modelClass \me\schema\Record */
        $modelClass = new $this->modelClass;
        $modelClass->populate($row);

        return $modelClass;
    }
    //
    public function count($column = '*') {
        return $this->queryScalar("COUNT($column)");
    }
    public function sum($column) {
        return $this->queryScalar("SUM($column)");
    }
    public function average($column) {
        return $this->queryScalar("AVG($column)");
    }
    public function min($column) {
        return $this->queryScalar("MIN($column)");
    }
    public function max($column) {
        return $this->queryScalar("MAX($column)");
    }
    protected function queryScalar($select) {
        $q = new self([
            'connection' => $this->connection,
            'modelClass' => $this->modelClass,
            'select'     => [$select],
            'from'       => $this->from,
            'where'      => $this->where
        ]);
        [$sql, $params] = $this->getQueryBuilder()->build($q);
        return $this->getCommand()->queryScalar($sql, $params);
    }
}