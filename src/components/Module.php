<?php
namespace me\components;
use me\core\Component;
use me\core\Container;
use me\helpers\StringHelper;
use me\exceptions\Exception;
/**
 * 
 */
class Module extends Component {
    /**
     * @var string Module ID
     */
    public $id;
    /**
     * @var string Default Route
     */
    public $defaultRoute = 'default/index';
    /**
     * @var string Controller Namespace
     */
    public $controllerNamespace;
    /**
     * @var string Module Namespace
     */
    public $moduleNamespace;
    /**
     * @var \me\components\Module|null Parent Object or null (root module)
     */
    public $parent;
    /**
     * @param string $controller_id Route
     * @return array [\me\components\Controller $controller, string $action_id]
     */
    public function createController($controller_id) {
        if ($controller_id === '') {
            $controller_id = $this->defaultRoute;
        }

        $id    = $controller_id;
        $route = '';
        if (strpos($controller_id, '/') !== false) {
            [$id, $route] = explode('/', $controller_id, 2);
        }

        $module = $this->getModule($id);
        if ($module !== null) {
            return $module->createController($route);
        }

        $name      = StringHelper::id2name($id);
        $className = $this->controllerNamespace . "\\{$name}Controller";
        if (!class_exists($className)) { //  || !($className instanceof Controller)
            throw new Exception("Controller { $className } Not Found", 12001);
        }

        $controller = Container::build($className, ['id' => $id, 'parent' => $this]);
        return [$controller, $route];
    }
    /**
     * @param string $id Module ID
     * @return \me\components\Module|null Module Object or null
     */
    public function getModule($id) {
        $name      = StringHelper::id2name($id);
        $className = $this->moduleNamespace . "\\$name\\Module";
        if (!class_exists($className)) { // || !($className instanceof Module)
            return null;
        }
        return Container::build($className, ['id' => $id, 'parent' => $this]);
    }
}