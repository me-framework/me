<?php
namespace me\validators;
use me\core\Cache;
use me\model\Validator;
class SyncValidator extends Validator {
    /**
     * 
     */
    public $relation_class;
    /**
     * 
     */
    public $source_id;
    /**
     * 
     */
    public $relation_source_id;
    /**
     * 
     */
    public $relation_target_id;
    /**
     * @param string $options Options
     */
    public function setOptions($options) {
        $config = explode(',', $options);
        if (isset($config[0]) && !empty($config[0])) {
            $this->relation_class = $config[0];
        }
        if (isset($config[1]) && !empty($config[1])) {
            $this->source_id = $config[1];
        }
        if (isset($config[2]) && !empty($config[2])) {
            $this->relation_source_id = $config[2];
        }
        if (isset($config[3]) && !empty($config[3])) {
            $this->relation_target_id = $config[3];
        }
    }
    /**
     * @param \me\Record $model Model
     * @param string $attribute Attribute Name
     * @param string $modelKey
     */
    public function validateAttribute($model, $attribute, $modelKey) {
        $rows = $model->$attribute;
        if ($rows === null) {
            return;
        }
        if (!is_array($rows)) {
            $model->addError($attribute, 'sync');
            return;
        }

        /* @var $relation_class \me\Record */
        $relation_class     = $this->relation_class;
        $source_id_name     = $this->source_id;
        $source_id          = $model->$source_id_name;
        $relation_source_id = $this->relation_source_id;
        $relation_target_id = $this->relation_target_id;
        $classes            = [];
        foreach ($rows as $index => $target_id) {
            /* @var $class \me\Record */
            $class = null;
            if ($source_id && $target_id) {
                $class = $relation_class::findOne([
                            $relation_source_id => $source_id,
                            $relation_target_id => $target_id
                ]);
            }
            if ($class === null) {
                $class = new $relation_class();
            }
            $class->$relation_target_id = $target_id;
            if (!$class->validate(true, null, [$relation_source_id])) {
                $model->addErrors($attribute . '.' . $index, $class->getErrors());
            }
            $classes[] = $class;
        }
        Cache::setCache([$modelKey, 'sync', $attribute], [$this, $classes]);
    }
}