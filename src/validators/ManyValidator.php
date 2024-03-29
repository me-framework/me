<?php
namespace me\validators;
use me\Record;
use me\core\Cache;
use me\model\Validator;
use me\exceptions\Exception;
/**
 * 
 */
class ManyValidator extends Validator {
    /**
     * @var string the name of the Record class
     */
    public $target_class;
    /**
     * @var string the name of the source attribute
     */
    public $source_attribute;
    /**
     * @var string the name of the destination attribute
     */
    public $dest_attribute;
    /**
     * @var string the name of the destination Primary Key
     */
    public $dest_key;
    /**
     * @param string $options Options
     */
    public function setOptions($options) {
        $config = explode(',', $options);
        if (isset($config[0]) && !empty($config[0])) {
            $this->target_class = $config[0];
        }
        if (isset($config[1]) && !empty($config[1])) {
            $this->source_attribute = $config[1];
        }
        if (isset($config[2]) && !empty($config[2])) {
            $this->dest_attribute = $config[2];
        }
        if (isset($config[3]) && !empty($config[3])) {
            $this->dest_key = $config[3];
        }
    }
    /**
     * @param \me\Record $model Model
     * @param string $attribute Attribute Name
     * @param string $modelKey
     */
    public function validateAttribute($model, $attribute, $modelKey) {
        if ($this->target_class === null) {
            throw new Exception('The "target_class" property must be set.');
        }
        if ($this->dest_key === null) {
            //find dest key
        }
        if ($this->dest_attribute === null) {
            //find dest attribute
        }
        if ($this->source_attribute === null) {
            //find source attribute
        }
        $rows = $model->$attribute;
        if ($rows === null) {
            return;
        }
        if (!is_array($rows)) {
            return $model->addError($attribute, 'many');
        }
        $classes = [];
        foreach ($rows as $index => $row) {
            $class = $this->findClass($model, $row);
            if (!$class->validate(true, null, [$this->dest_attribute])) {
                $model->addErrors($attribute . '.' . $index, $class->getErrors());
            }
            $classes[] = $class;
        }
        $model->$attribute = $classes;
        Cache::setCache([$modelKey, 'many', $attribute], $this);
    }
    /**
     * @return \me\Record Record
     */
    private function findClass($model, $row) {
        /* @var $class_name \me\Record */
        $class_name      = $this->target_class; // users_mobiles
        $source_id_name  = $this->source_attribute; // id
        $source_id       = $model->$source_id_name; // 1
        $dest_field_name = $this->dest_attribute; // user_id
        $dest_id_name    = $this->dest_key; // id
        
        /* @var $class \me\Record */
        $class = new $class_name();
        if ($row instanceof Record) {
            $class = $row;
        }
        else {
            if ($source_id && isset($row[$dest_id_name])) {
                $class = $class_name::findOne([$dest_id_name => $row[$dest_id_name], $dest_field_name => $source_id]);
            }
            $class->load($row);
        }
        return $class;
    }
}