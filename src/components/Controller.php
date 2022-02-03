<?php
namespace me\components;
use Me;
use Exception;
use ReflectionMethod;
/**
 * 
 */
class Controller extends Component {
    /**
     * @var string Controller ID
     */
    public $id;
    /**
     * @var \me\components\Module Module Object
     */
    public $parent;
    /**
     * @var string Default Action
     */
    public $defaultAction = 'index';
    /**
     * @param string $action_id Action ID
     * @param array $params Parameters
     * @return \me\components\Response|mixed Response
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
     * @return \me\components\Action Action Object
     */
    public function create_action(string $action_id) {
        $methodName = str_replace(' ', '', ucwords(str_replace('-', ' ', $action_id)));
        if (method_exists($this, $methodName)) {
            $method = new ReflectionMethod($this, $methodName);
            if ($method->isPublic()) {
                return Me::createObject([
                            'class'        => Action::class,
                            'id'           => $action_id,
                            'parent'       => $this,
                            'actionMethod' => $methodName
                ]);
            }
        }
        throw new Exception("Action { $methodName } Not Found", 13001);
    }
}