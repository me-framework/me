<?php
namespace me\validators;
use me\model\Validator;
class sync extends Validator {
    public $relation;
    /**
     * @param string $options Options
     */
    public function setOptions($options) {
//        $config = explode(',', $options);
//        if (isset($config[0]) && !empty($config[0])) {
//            $this->relation = $config[0];
//        }
    }
    /**
     * @param \me\Record $model Model
     * @param string $attribute Attribute Name
     */
    public function validateAttribute($model, $attribute) {
//        $value = $model->$attribute;
//        if (
//            $this->relation === null || $value === null ||
//            !is_array($value) || $value === []
//        ) {
//            $model->$attribute = [];
//            return;
//        }
    }
}