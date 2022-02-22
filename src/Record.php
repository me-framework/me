<?php
namespace me;
use Me;
use Exception;
use me\core\Cache;
use me\model\Model;
use me\helpers\ArrayHelper;
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
    //
    //
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
        $rowCount  = static::deleteAll($condition);
        if (!$rowCount) {
            return false;
        }
        $this->_oldAttributes = null;
        return true;
    }
    /**
     * 
     */
    public function populate($row) {
        $columns = static::getTableSchema()->columns;
        foreach ($row as $name => $value) {
            if (isset($columns[$name])) {
                $this->_attributes[$name] = $columns[$name]->phpTypecast($value);
            }
            //elseif ($this->canSetProperty($name)) {
            //    $this->$name = $columns[$name]->phpTypecast($value);
            //}
        }
        $this->_oldAttributes = $this->_attributes;
        return $this;
    }
    /**
     * 
     */
    public function addMany($attribute, $many) {
        $key = $this->getKey();
        Cache::setCache([$key, 'many', $attribute], $many);
    }
    /**
     * 
     */
    public function __get($name) {
        if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }
        if ($this->hasAttribute($name)) {
            return null;
        }
        return parent::__get($name);
    }
    /**
     * 
     */
    public function __set($name, $value) {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        }
        else {
            parent::__set($name, $value);
        }
    }
    //
    //
    //
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
    //
    //
    //
    /**
     * @return bool
     */
    protected function insert() {
        $values     = $this->getDirtyAttributes();
        $connection = $this->getConnection();
        [$sql, $params] = $this->getQueryBuilder()->insert(static::tableName(), $values);
        $rowCount   = $this->getCommand()->execute($connection, $sql, $params);
        if (!$rowCount) {
            return false;
        }
        $tableSchema = static::getTableSchema();
        $primaryKeys = $tableSchema->primaryKey;
        $columns     = $tableSchema->columns;
        foreach ($primaryKeys as $name) {
            if ($columns[$name]->autoIncrement) {
                $value                    = $this->getConnection()->lastInsertId($tableSchema->sequenceName);
                $id                       = $columns[$name]->phpTypecast($value);
                $this->_attributes[$name] = $id;
                $values[$name]            = $id;
            }
        }
        $this->_oldAttributes = $values;
        $this->many();
        return true;
    }
    /**
     * @return bool
     */
    protected function update() {
        $columns = $this->getDirtyAttributes();
        if ($columns) {
            $condition = $this->getOldPrimaryKeys();
            $rowCount  = static::updateAll($columns, $condition);
            if (!$rowCount) {
                return false;
            }
            foreach ($columns as $name => $value) {
                $this->_oldAttributes[$name] = $value;
            }
        }
        $this->many();
        return true;
    }
    /**
     * 
     */
    protected function many() {
        $key  = $this->getKey();
        $many = Cache::getCache([$key, 'many'], []);
        foreach ($many as $attribute => $validator) {
            /* @var $validator validators\many */
            /* @var $models Record[] */
            $source_id_name  = $validator->sourceAttribute;
            $source_id       = $this->$source_id_name;
            $dest_field_name = $validator->destAttribute;
            $dest_id_name    = $validator->destKey;
            $class_name      = $validator->targetClass;
            $ids             = [];
            $items           = [];
            $models          = $this->$attribute;
            foreach ($models as $model) {
                $model->$dest_field_name = $source_id;
                $model->save();
                $ids[]                   = $model->$dest_id_name;
                $items[]                 = $model;
            }
            $class_name::deleteAll(['and', [$dest_field_name => $source_id], ['not in', $dest_id_name, $ids]]);
        }
    }
    /**
     * 
     */
    protected function getDirtyAttributes() {
        $attributes       = $this->attributes();
        $names            = array_flip($attributes);
        $dirty_attributes = [];
        if ($this->_oldAttributes === null) {
            foreach ($this->_attributes as $name => $value) {
                if (isset($names[$name])) {
                    $dirty_attributes[$name] = $value;
                }
            }
        }
        else {
            foreach ($this->_attributes as $name => $value) {
                if (isset($names[$name]) && (!array_key_exists($name, $this->_oldAttributes) || $value !== $this->_oldAttributes[$name])) {
                    $dirty_attributes[$name] = $value;
                }
            }
        }
        return $dirty_attributes;
    }
    /**
     * 
     */
    protected function getOldPrimaryKeys() {
        $primaryKeys = static::primaryKeys();
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
    protected function hasAttribute($name) {
        return isset($this->_attributes[$name]) || in_array($name, $this->attributes(), true);
    }
    /**
     * 
     */
    protected function getValidatorsMap() {
        return array_merge(parent::getValidatorsMap(), [
            'exists' => 'me\validators\exists',
            'many'   => 'me\validators\many',
            'sync'   => 'me\validators\sync',
            'unique' => 'me\validators\unique',
        ]);
    }
    /**
     * 
     */
    protected function attributes() {
        return array_keys(static::getTableSchema()->columns);
    }
    /**
     * 
     */
    protected function fields() {
        return array_keys($this->_attributes);
    }
    //
    //
    //
    //
    /**
     * @return \me\database\DatabaseManager Database Manager
     */
    protected static function getDatabase() {
        return Me::$app->get('database');
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