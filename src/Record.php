<?php
namespace me;
use Me;
use Exception;
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
    private $_attributes = [];
    /**
     * @var array|null old attribute values indexed by attribute names.
     * This is `null` if the record [[isNewRecord|is new]].
     */
    private $_oldAttributes;
    /**
     * @return \me\database\DatabaseManager Database Manager
     */
    private static function getDatabase() {
        return Me::$app->get('database');
    }
    /**
     * @return \me\database\Connection Connection
     */
    private static function getConnection() {
        return self::getDatabase()->getConnection(static::$connection);
    }
    /**
     * @return \me\database\Command Command
     */
    private static function getCommand() {
        return self::getDatabase()->getCommand();
    }
    /**
     * @return \me\database\Schema Schema
     */
    private static function getSchema() {
        return self::getDatabase()->getSchema(static::$connection);
    }
    /**
     * @param string $modelClass Model Class
     * @return \me\database\Query Query
     */
    private static function createQuery($modelClass) {
        return self::getSchema()->createQuery($modelClass);
    }
    /**
     * @return \me\database\QueryBuilder Query Builder
     */
    private static function getQueryBuilder() {
        return self::getSchema()->getQueryBuilder();
    }
    /**
     * @return \me\database\TableSchema Table Schema
     */
    private static function getTableSchema() {
        return self::getSchema()->getTableSchema(static::tableName());
    }
    /**
     * 
     */
    private static function primaryKeys() {
        return self::getTableSchema()->primaryKey;
    }
    /**
     * 
     */
    public static function tableName() {
        return basename(get_called_class());
    }
    /**
     * 
     */
    public function attributes() {
        return array_keys(self::getTableSchema()->columns);
    }
    /**
     * 
     */
    public function hasAttribute($name) {
        return isset($this->_attributes[$name]) || in_array($name, $this->attributes(), true);
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
    /**
     * @return \me\database\Query Query
     */
    public static function find() {
        return self::createQuery(get_called_class());
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
     * @param array|string $condition Condition
     * @return \me\database\Query Query
     */
    protected static function findByCondition($condition) {
        $query = static::find();
        if (!ArrayHelper::isAssociative($condition)) {
            $primaryKeys = self::primaryKeys();
            if (!isset($primaryKeys[0])) {
                throw new Exception('"' . get_called_class() . '" must have a primary key.');
            }
            $condition = [$primaryKeys[0] => is_array($condition) ? array_values($condition) : $condition];
        }
        return $query->andWhere($condition);
    }
    /**
     * @param array $columns
     * @param array $condition
     * @return int Affected Rows
     */
    public static function updateAll($columns, $condition) {
        $connection = $this->getConnection();
        [$sql, $params] = $this->getQueryBuilder()->update(static::tableName(), $columns, $condition);
        return $this->getCommand()->execute($connection, $sql, $params);
    }
    /**
     * @param array $condition
     * @return int Affected Rows
     */
    public static function deleteAll($condition) {
        $connection = $this->getConnection();
        [$sql, $params] = $this->getQueryBuilder()->delete(static::tableName(), $condition);
        return $this->getCommand()->execute($connection, $sql, $params);
    }
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
    private function insert() {
        $values     = $this->getDirtyAttributes();
        
        $connection = $this->getConnection();
        [$sql, $params] = $this->getQueryBuilder()->insert(static::tableName(), $values);
        $rowCount   = $this->getCommand()->execute($connection, $sql, $params);
        if (!$rowCount) {
            return false;
        }
        
        $tableSchema = self::getTableSchema();
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
        return true;
    }
    /**
     * @return bool
     */
    private function update() {
        $columns   = $this->getDirtyAttributes();
        $condition = $this->getOldPrimaryKeys();
        $rowCount  = static::updateAll($columns, $condition);
        if (!$rowCount) {
            return false;
        }
        foreach ($columns as $name => $value) {
            $this->_oldAttributes[$name] = $value;
        }
        return true;
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
        $columns = self::getTableSchema()->columns;
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
     * Returns a value indicating whether the current record is new.
     * @return bool whether the record is new and should be inserted when calling [[save()]].
     */
    public function getIsNewRecord() {
        return $this->_oldAttributes === null;
    }
    /**
     * 
     */
    public function fields() {
        return array_keys($this->_attributes);
    }
    /**
     * 
     */
    private function getDirtyAttributes() {
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
    private function getOldPrimaryKeys() {
        $primaryKeys = self::primaryKeys();
        if (empty($primaryKeys)) {
            throw new Exception(get_class($this) . ' does not have a primary key. You should either define a primary key for the corresponding table or override the primaryKeys() method.');
        }
        $values = [];
        foreach ($primaryKeys as $name) {
            $values[$name] = isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
        }
        return $values;
    }
}