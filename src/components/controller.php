<?php
namespace me\components;
use Me;
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
     * @var string Default Action
     */
    public $default_action = 'index';
    /**
     * @var \me\components\module Module Object
     */
    public $parent;
    /**
     * @param string $action_id Action ID
     * @param array $params Parameters
     * @return \me\components\response|mixed Response
     */
    public function run_action(string $action_id, array $params = []) {
        $action = $this->create_action($action_id);
        $this->beforeAction($action);
        $result = $action->run($params);
        return $this->afterAction($action, $result);
    }
    /**
     * @param string $action_id Action ID
     * @return \me\components\action Action Object
     */
    public function create_action(string $action_id) {
        if ($action_id === '') {
            $action_id = $this->default_action;
        }

        $name = str_replace('-', '_', strtolower($action_id));
        if (!method_exists($this, $name)) {
            throw new Exception("Action { $name } Not Found", 13001);
        }

        $method = new ReflectionMethod($this, $name);
        if (!$method->isPublic()) {
            throw new Exception("Action { $name } Not Found", 13001);
        }

        return Container::build(['class' => action::class, 'id' => $action_id, 'actionMethod' => $name, 'parent' => $this]);
    }
    /**
     * @param \me\components\action $action Action Object
     * @return bool
     */
    protected function beforeAction($action) {
        $user    = null;
        $request = Me::$app->getRequest();
        $access  = $this->access();
        if (empty($access) || !isset($access[$action->id])) {
            return;
        }
        if (!$this->matchRole($access[$action->id], $user)) {
            throw new Exception('Access Denied: Role');
        }
        if (!$this->matchMethods($access[$action->id], $request->getMethod())) {
            throw new Exception('Access Denied: Methods');
        }
        if (!$this->matchIP($access[$action->id], $request->getUserIP())) {
            throw new Exception('Access Denied: IP');
        }
        if (!$this->matchCallback($access[$action->id], $action)) {
            throw new Exception('Access Denied: Callback');
        }
    }
    /**
     * 
     */
    protected function afterAction($action, $result) {
        return $action ? $result : $result;
    }
    /**
     * 
     */
    protected function access() {
        return [];
    }
    protected function matchRole($access, $user) {
        if (!isset($access['roles']) || empty($access['roles'])) {
            return true;
        }
        return true;
    }
    protected function matchMethods($access, $method) {
        if (!isset($access['methods']) || empty($access['methods'])) {
            return true;
        }
        return in_array($method, $access['methods'], true);
    }
    protected function matchIP($access, $ip) {
        if (!isset($access['ip']) || empty($access['ip'])) {
            return true;
        }
        foreach ($access['ip'] as $rule) {
            if ($rule === '*' || $rule === $ip || ($ip !== null && ($pos = strpos($rule, '*')) !== false && strncmp($ip, $rule, $pos) === 0)) {
                return true;
            }
        }
        return false;
    }
    protected function matchCallback($access, $action) {
        if (!isset($access['callback']) || !is_callable($access['callback'])) {
            return true;
        }
        return call_user_func($access['callback'], $action);
    }
}