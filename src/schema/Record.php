<?php
namespace me\schema;
use Me;
use Exception;
use me\model\Model;
use me\helpers\ArrayHelper;
class Record extends Model {
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
     * 
     */
    public static function tableName() {
        return basename(get_called_class());
    }
    /**
     * 
     */
    public static function getTableSchema() {
        /* @var $schema SchemaManager */
        $schema = Me::$app->get('schema');
        return $schema->getTableSchema(static::tableName(), static::$connection);
    }
    /**
     * 
     */
    public static function primaryKeys() {
        return static::getTableSchema()->primaryKey;
    }
    /**
     * 
     */
    public function attributes() {
        return array_keys(static::getTableSchema()->columns);
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
     * @return \me\schema\Query Query
     */
    public static function find() {
        return new Query(['modelClass' => get_called_class(), 'connection' => static::$connection]);
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
     * @return \me\schema\Query Query
     */
    protected static function findByCondition($condition) {
        $query = static::find();
        if (!ArrayHelper::isAssociative($condition)) {
            $primaryKey = static::primaryKeys();
            if (!isset($primaryKey[0])) {
                throw new Exception('"' . get_called_class() . '" must have a primary key.');
            }
            $condition = [$primaryKey[0] => is_array($condition) ? array_values($condition) : $condition];
        }
        return $query->andWhere($condition);
    }
    //
    public static function insertAll($columns, $rows) {
        
    }
    public static function updateAll($columns, $condition) {
        
    }
    public static function deleteAll($condition) {
        
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
        
    }
    /**
     * @return bool
     */
    private function update() {
        
    }
    /**
     * @return bool
     */
    public function delete() {
        
    }
    //
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
     * Returns a value indicating whether the current record is new.
     * @return bool whether the record is new and should be inserted when calling [[save()]].
     */
    public function getIsNewRecord() {
        return $this->_oldAttributes === null;
    }
}