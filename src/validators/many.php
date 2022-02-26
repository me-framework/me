<?php
namespace me\validators;
use Exception;
use me\model\Validator;
use me\Record;
use me\core\Cache;
class many extends Validator {
    /**
     * @var string the name of the Record class
     */
    public $targetClass;
    /**
     * @var string the name of the source attribute
     */
    public $sourceAttribute;
    /**
     * @var string the name of the destination attribute
     */
    public $destAttribute;
    /**
     * @var string the name of the destination Primary Key
     */
    public $destKey;
    /**
     * @param string $options Options
     */
    public function setOptions($options) {
        $config = explode(',', $options);
        if (isset($config[0]) && !empty($config[0])) {
            $this->targetClass = $config[0];
        }
        if (isset($config[1]) && !empty($config[1])) {
            $this->sourceAttribute = $config[1];
        }
        if (isset($config[2]) && !empty($config[2])) {
            $this->destAttribute = $config[2];
        }
        if (isset($config[3]) && !empty($config[3])) {
            $this->destKey = $config[3];
        }
    }
    /**
     * @param \me\Record $model Model
     * @param string $attribute Attribute Name
     * @param string $modelKey
     */
    public function validateAttribute($model, $attribute, $modelKey) {
        if ($this->targetClass === null) {
            throw new Exception('The "targetClass" property must be set.');
        }
        if ($this->destKey === null) {
            //find dest key
        }
        if ($this->destAttribute === null) {
            //find dest attribute
        }
        if ($this->sourceAttribute === null) {
            //find source attribute
        }

        $rows = $model->$attribute;
        if ($rows === null) {
            return;
        }
        if (!is_array($rows)) {
            $model->addError($attribute, 'many');
            return;
        }

        /* @var $class_name \me\Record */
        $source_id_name  = $this->sourceAttribute;
        $source_id       = $model->$source_id_name;
        $dest_field_name = $this->destAttribute;
        $dest_id_name    = $this->destKey;
        $class_name      = $this->targetClass;

        $classes = [];
        foreach ($rows as $index => $row) {
            /* @var $class \me\Record */
            $class = null;
            if ($row instanceof Record) {
                $class = $row;
            }
            else {
                unset($row[$dest_field_name]);
                if ($source_id && isset($row[$dest_id_name])) {
                    $class = $class_name::findOne([
                                $dest_id_name    => $row[$dest_id_name],
                                $dest_field_name => $source_id
                    ]);
                }
                unset($row[$dest_id_name]);
                if ($class === null) {
                    $class = new $class_name();
                }
                $class->load($row);
            }
            if (!$class->validate(true, null, [$dest_field_name])) {
                $model->addErrors($attribute . '.' . $index, $class->getErrors());
            }
            $classes[] = $class;
        }
        $model->$attribute = $classes;
        Cache::setCache([$modelKey, 'many', $attribute], $this);
    }
}