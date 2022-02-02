<?php
namespace me\components;
use Exception;
use ReflectionMethod;
/**
 * 
 */
class Action extends Component {
    /**
     * @var string
     */
    public $id;
    /**
     * @var Controller
     */
    public $parent;
    /**
     * @var string
     */
    public $actionMethod;
    /**
     * 
     */
    public function run($params) {
        $method = new ReflectionMethod($this->parent, $this->actionMethod);
        $args   = [];
        foreach ($method->getParameters() as $param) {
            /* @var $param \ReflectionParameter */
            if (isset($params[$param->name])) {
                $args[$param->name] = $params[$param->name];
            }
            else if ($param->isOptional()) {
                $args[$param->name] = $param->getDefaultValue();
            }
            else {
                throw new Exception("Parameter { $param->name } Is Missing", 14001);
            }
        }
        return call_user_func_array([$this->parent, $this->actionMethod], $args);
    }
}