<?php
namespace me\validators;
use Exception;
use me\model\Validator;
class exists extends Validator {
    /**
     * @var string the name of the Record class
     */
    public $targetClass;
    /**
     * @var string the name of the Record attribute
     */
    public $targetAttribute;
    /**
     * @param string $options Options
     */
    public function setOptions($options) {
        $config = explode(',', $options);
        if (isset($config[0]) && !empty($config[0])) {
            $this->targetClass = $config[0];
        }
        if (isset($config[1]) && !empty($config[1])) {
            $this->targetAttribute = $config[1];
        }
    }
    /**
     * @param \me\Record $model Model
     * @param string $attribute Attribute Name
     */
    public function validateAttribute($model, $attribute) {
        if ($this->targetClass === null) {
            throw new Exception('The "targetClass" property must be set.');
        }
        if (!is_string($this->targetAttribute)) {
            throw new Exception('The "targetAttribute" property must be configured as a string.');
        }
        $value = $model->$attribute;
        if ($value !== null) {
            /* @var $record null|\me\Record */
            $record = $this->targetClass::find()->andWhere([$this->targetAttribute => $value])->one();
            if ($record === null) {
                $model->addError($attribute, 'exists');
            }
        }
    }
}