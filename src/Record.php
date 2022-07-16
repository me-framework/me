<?php
namespace me;
use Me;
use Exception;
use me\core\Cache;
use me\model\Model;
use me\validators;
use me\core\helpers\ArrayHelper;
use me\database\RecordInterface;
class Record extends Model implements RecordInterface {
    /**
     * @var string Connection Name
     */
    protected static $connection;
    /**
     * @var array attribute values indexed by attribute names
     */
    protected $_attributes = [];
    /**
     * @var array|null old attribute values indexed by attribute names.
     * This is `null` if the record [[isNewRecord|is new]].
     */
    protected $_oldAttributes;
    //
    /**
     * @param bool $runValidation Run Validation
     * @return bool
     */
    public function save($runValidation = true) {
        if ($runValidation && !$this->validate()) {
            return false;
        }
        if ($this->getIsNewRecord()) {
            return $this->insert();
        }
        return $this->update();
    }
    /**
     * @return bool
     */
    public function delete() {
        $condition = $this->getOldPrimaryKeys();
        $rowCount  = $this->deleteAll($condition);
        if (!$rowCount) {
            return false;
        }
        $this->_oldAttributes = null;
        return true;
    }
    /**
     * @param array $row
     * @param \me\database\Query $query
     */
    public function populate($row, $query) {
        $columns = $this->columns();
        foreach ($row as $name => $value) {
            if (isset($columns[$name])) {
                $this->_attributes[$name] = $columns[$name]->phpTypecast($value);
            }
            //elseif ($this->canSetProperty($name)) {
            //    $this->$name = $columns[$name]->phpTypecast($value);
            //}
        }
        $this->_oldAttributes = $this->_attributes;
        return $this->populateWith($query->with);
    }
    /**
     * 
     */
    public function __get($name) {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }
        if (array_key_exists($name, $this->columns()) || array_key_exists($name, $this->rules())) {
            return null;
        }
        return parent::__get($name);
    }
    /**
     * 
     */
    public function __set($name, $value) {
        if (array_key_exists($name, $this->columns()) || array_key_exists($name, $this->rules())) {
            $this->_attributes[$name] = $value;
        }
        else {
            parent::__set($name, $value);
        }
    }
    //
    /**
     * 
     */
    public static function tableName() {
        return basename(get_called_class());
    }
    /**
     * @return \me\database\Query Query
     */
    public static function find() {
        return static::createQuery(get_called_class());
    }
    /**
     * @param array|string $condition Condition
     * @return self Record Model
     */
    public static function findOne($condition) {
        return static::findByCondition($condition)->one();
    }
    /**
     * @param array|string $condition Condition
     * @return self[] Records Models
     */
    public static function findAll($condition) {
        return static::findByCondition($condition)->all();
    }
    /**
     * @param array $columns
     * @param array $condition
     * @return int Affected Rows
     */
    public static function updateAll($columns, $condition) {
        $connection = static::getConnection();
        [$sql, $params] = static::getQueryBuilder()->update(static::tableName(), $columns, $condition);
        return static::getCommand()->execute($connection, $sql, $params);
    }
    /**
     * @param array $condition
     * @return int Affected Rows
     */
    public static function deleteAll($condition) {
        $connection = static::getConnection();
        [$sql, $params] = static::getQueryBuilder()->delete(static::tableName(), $condition);
        return static::getCommand()->execute($connection, $sql, $params);
    }
    //
    /**
     * @return bool
     */
    protected function insert() {
        $values     = $this->getDirtyAttributes();
        $connection = $this->getConnection();
        [$sql, $params] = $this->getQueryBuilder()->insert($this->tableName(), $values);
        $rowCount   = $this->getCommand()->execute($connection, $sql, $params);
        if (!$rowCount) {
            return false;
        }
        $primaryKeys = $this->primaryKeys();
        $columns     = $this->columns();
        foreach ($primaryKeys as $name) {
            if ($columns[$name]->autoIncrement) {
                $value                    = $this->getConnection()->lastInsertId($this->getTableSchema()->sequenceName);
                $id                       = $columns[$name]->phpTypecast($value);
                $this->_attributes[$name] = $id;
                $values[$name]            = $id;
            }
        }
        $this->_oldAttributes = $values;
        $this->relations();
        return true;
    }
    /**
     * @return bool
     */
    protected function update() {
        $columns = $this->getDirtyAttributes();
        if ($columns) {
            $condition = $this->getOldPrimaryKeys();
            $rowCount  = $this->updateAll($columns, $condition);
            if (!$rowCount) {
                return false;
            }
            foreach ($columns as $name => $value) {
                $this->_oldAttributes[$name] = $value;
            }
        }
        $this->relations();
        return true;
    }
    /**
     * 
     */
    protected function relations() {
        $this->many();
        $this->sync();
    }
    /**
     * 
     */
    protected function many() {
        $many = Cache::getCache([$this->_key, 'many'], []);
        foreach ($many as $attribute => $validator) {
            /* @var $validator \me\validators\many */
            /* @var $models Record[] */
            $source_id_name  = $validator->source_attribute;
            $source_id       = $this->$source_id_name;
            $dest_field_name = $validator->dest_attribute;
            $dest_id_name    = $validator->dest_key;
            $class_name      = $validator->target_class;
            $ids             = [];
            $models          = $this->$attribute;
            foreach ($models as $model) {
                $model->$dest_field_name = $source_id;
                $model->save();
                $ids[]                   = $model->$dest_id_name;
            }
            $class_name::deleteAll(['and', [$dest_field_name => $source_id], ['not in', $dest_id_name, $ids]]);
        }
    }
    /**
     * 
     */
    protected function sync() {
        $sync = Cache::getCache([$this->_key, 'sync'], []);
        foreach ($sync as [$validator, $models]) {
            /* @var $validator \me\validators\sync */
            /* @var $models Record[] */
            $relation_class     = $validator->relation_class;
            $source_id_name     = $validator->source_id;
            $source_id          = $this->$source_id_name;
            $relation_source_id = $validator->relation_source_id;
            $relation_target_id = $validator->relation_target_id;
            $ids                = [];
            foreach ($models as $model) {
                $model->$relation_source_id = $source_id;
                $model->save();
                $ids[]                      = $model->$relation_target_id;
            }
            $relation_class::deleteAll(['and', [$relation_source_id => $source_id], ['not in', $relation_target_id, $ids]]);
        }
    }
    /**
     * 
     */
    protected function getDirtyAttributes() {
        $columns    = $this->columns();
        $attributes = [];
        if ($this->_oldAttributes === null) {
            foreach ($this->_attributes as $name => $value) {
                if (isset($columns[$name])) {
                    $attributes[$name] = $value;
                }
            }
        }
        else {
            foreach ($this->_attributes as $name => $value) {
                if (isset($columns[$name]) && (!array_key_exists($name, $this->_oldAttributes) || $value !== $this->_oldAttributes[$name])) {
                    $attributes[$name] = $value;
                }
            }
        }
        return $attributes;
    }
    /**
     * 
     */
    protected function getOldPrimaryKeys() {
        $primaryKeys = $this->primaryKeys();
        if (empty($primaryKeys)) {
            throw new Exception(get_class($this) . ' does not have a primary key. You should either define a primary key for the corresponding table or override the primaryKeys() method.');
        }
        $values = [];
        foreach ($primaryKeys as $name) {
            $values[$name] = isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
        }
        return $values;
    }
    /**
     * @return bool
     */
    protected function getIsNewRecord() {
        return $this->_oldAttributes === null;
    }
    /**
     * 
     */
    protected function attributes() {
        return array_keys($this->_attributes);
    }
    /**
     * 
     */
    protected function getValidatorsMap() {
        return array_merge(parent::getValidatorsMap(), [
            'exists' => validators\exists::class,
            'many'   => validators\many::class,
            'sync'   => validators\sync::class,
            'unique' => validators\unique::class,
        ]);
    }
    /**
     * 
     */
    protected function hasRelation($attributes_rules, $attribute) {
        if (!isset($attributes_rules[$attribute])) {
            return false;
        }
        $rules = $attributes_rules[$attribute];
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        if (!is_array($rules)) {
            return false;
        }
        foreach ($rules as $rule) {
            if (strpos($rule, 'many') !== false || strpos($rule, 'sync') !== false) {
                return $rule;
            }
        }
        return false;
    }
    /**
     * 
     */
    protected function populateWith($with) {
        if (!$with) {
            return $this;
        }
        $attributes_rules = $this->rules();
        $validatorsMap    = $this->getValidatorsMap();
        foreach ($with as $name) {
            $rule = $this->hasRelation($attributes_rules, $name);
            if (!$rule) {
                throw new Exception('No Relation Found: "' . $name . '"');
            }
            $validator = $this->createRule($validatorsMap, $rule);
            if ($validator instanceof validators\sync) {
                $relation_class     = $validator->relation_class;
                $source_id          = $validator->source_id;
                $relation_source_id = $validator->relation_source_id;
                $relation_target_id = $validator->relation_target_id;

                $items  = [];
                $models = $relation_class::find()->select([$relation_target_id])->where([$relation_source_id => $this->$source_id])->all();
                foreach ($models as $model) {
                    $items[] = $model->$relation_target_id;
                }
                $this->$name = $items;
            }
            elseif ($validator instanceof validators\many) {
                $target_class     = $validator->target_class;
                $source_attribute = $validator->source_attribute;
                $dest_attribute   = $validator->dest_attribute;
                $models           = $target_class::findAll([$dest_attribute => $this->$source_attribute]);
                $this->$name      = $models;
            }
        }
        return $this;
    }
    //
    /**
     * @return \me\database\DatabaseManager Database Manager
     */
    protected static function getDatabase() {
        return Me::$app->getDatabase();
    }
    /**
     * @return \me\database\Connection Connection
     */
    protected static function getConnection() {
        return static::getDatabase()->getConnection(static::$connection);
    }
    /**
     * @return \me\database\Command Command
     */
    protected static function getCommand() {
        return static::getDatabase()->getCommand();
    }
    /**
     * @return \me\database\Schema Schema
     */
    protected static function getSchema() {
        return static::getDatabase()->getSchema(static::$connection);
    }
    /**
     * @param string $modelClass Model Class
     * @return \me\database\Query Query
     */
    protected static function createQuery($modelClass) {
        return static::getSchema()->createQuery($modelClass);
    }
    /**
     * @return \me\database\QueryBuilder Query Builder
     */
    protected static function getQueryBuilder() {
        return static::getSchema()->getQueryBuilder();
    }
    /**
     * @return \me\database\TableSchema Table Schema
     */
    protected static function getTableSchema() {
        return static::getSchema()->getTableSchema(static::tableName());
    }
    /**
     * 
     */
    protected static function primaryKeys() {
        return static::getTableSchema()->primaryKey;
    }
    /**
     * 
     */
    protected static function columns() {
        return static::getTableSchema()->columns;
    }
    /**
     * @param array|string $condition Condition
     * @return \me\database\Query Query
     */
    protected static function findByCondition($condition) {
        $query = static::find();
        if (!ArrayHelper::isAssociative($condition)) {
            $primaryKeys = static::primaryKeys();
            if (!isset($primaryKeys[0])) {
                throw new Exception('"' . get_called_class() . '" must have a primary key.');
            }
            $condition = [$primaryKeys[0] => is_array($condition) ? array_values($condition) : $condition];
        }
        return $query->andWhere($condition);
    }
}