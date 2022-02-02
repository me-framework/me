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
     * @var string
     */
    public $id;
    /**
     * @var Module
     */
    public $parent;
    /**
     * @var string
     */
    public function run_action(string $actionID, array $params = []) {
        $action = $this->create_action($actionID);
        return $action->run($params);
    }
    /**
     * @param string $actionID
     * @return Action
     */
    public function create_action(string $actionID) {
        $methodName = str_replace(' ', '', ucwords(str_replace('-', ' ', $actionID)));
        if (method_exists($this, $methodName)) {
            $method = new ReflectionMethod($this, $methodName);
            if ($method->isPublic()) {
                return Me::createObject([
                            'class'        => Action::class,
                            'id'           => $actionID,
                            'parent'       => $this,
                            'actionMethod' => $methodName
                ]);
            }
        }
        throw new Exception("Action { $methodName } Not Found", 13001);
    }
}