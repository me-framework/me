<?php
namespace me\components;
use Exception;
use ReflectionMethod;
use me\core\Component;
/**
 * 
 */
class action extends Component {
    /**
     * @var string Action ID
     */
    public $id;
    /**
     * @var string Action Method
     */
    public $actionMethod;
    /**
     * @var \me\components\controller Controller Object
     */
    public $parent;
    /**
     * @param array $params Parameters
     * @return \me\components\response|mixed Response
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