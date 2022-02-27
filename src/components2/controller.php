<?php
namespace me\components;
use Exception;
use ReflectionMethod;
use me\core\Component;
use me\core\components\Container;
/**
 * 
 */
class controller extends Component {
    /**
     * @var string Controller ID
     */
    public $id;
    /**
     * @var \me\components\module Module Object
     */
    public $parent;
    /**
     * @var string Default Action
     */
    public $defaultAction = 'index';
    /**
     * @param string $action_id Action ID
     * @param array $params Parameters
     * @return \me\components\response|mixed Response
     */
    public function run_action(string $action_id, array $params = []) {
        if ($action_id === '') {
            $action_id = $this->defaultAction;
        }

        $action = $this->create_action($action_id);
        return $action->run($params);
    }
    /**
     * @param string $action_id Action ID
     * @return \me\components\action Action Object
     */
    public function create_action(string $action_id) {
        $methodName = str_replace('-', '_', strtolower($action_id));
        if (method_exists($this, $methodName)) {
            $method = new ReflectionMethod($this, $methodName);
            if ($method->isPublic()) {
                return Container::build([
                            'class'        => action::class,
                            'id'           => $action_id,
                            'parent'       => $this,
                            'actionMethod' => $methodName
                ]);
            }
        }
        throw new Exception("Action { $methodName } Not Found", 13001);
    }
}