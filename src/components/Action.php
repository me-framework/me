<?php
namespace me\components;
use ReflectionMethod;
use me\core\Component;
use me\exceptions\Exception;
/**
 * 
 */
class Action extends Component {
    /**
     * @var string Action ID
     */
    public $id;
    /**
     * @var string Action Method
     */
    public $actionMethod;
    /**
     * @var \me\components\Controller Controller Object
     */
    public $parent;
    /**
     * @param array $params Parameters
     * @return \me\components\Response|mixed Response
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