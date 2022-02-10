<?php
namespace me\components;
use Me;
use Exception;
use me\core\Component;
/**
 * 
 */
class Module extends Component {
    /**
     * @var string Module ID
     */
    public $id;
    /**
     * @var \me\components\Module|null Parent Object or null (root module)
     */
    public $parent;
    /**
     * @var string Controller Namespace
     */
    public $controllerNamespace;
    /**
     * @var string Module Namespace
     */
    public $moduleNamespace;
    /**
     * @var string Default Route
     */
    public $defaultRoute = 'default/index';
    /**
     * @param string $route Route
     * @return array [\me\components\Controller $controller, string $action_id]
     */
    public function create_controller($route) {

        if ($route === '') {
            $route = $this->defaultRoute;
        }

        if (strpos($route, '/') !== false) {
            list($id, $route2) = explode('/', $route, 2);
        }
        else {
            $id     = $route;
            $route2 = '';
        }

        $module = $this->get_module($id);
        if (!is_null($module)) {
            return $module->create_controller($route2);
        }

        $name      = str_replace(' ', '', ucwords(str_replace('-', ' ', $id))) . 'Controller';
        $className = "$this->controllerNamespace\\$name";
        if (!class_exists($className) || !is_subclass_of($className, Controller::class)) {
            throw new Exception("Controller { $className } Not Found", 12001);
        }

        $controller = Me::createObject(['class' => $className, 'id' => $id, 'parent' => $this]);
        return [$controller, $route2];
    }
    /**
     * @param string $id Module ID
     * @return \me\components\Module|null Module Object or null
     */
    public function get_module($id) {

        $name      = str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
        $className = $this->moduleNamespace . "\\$name\\module";

        if (!class_exists($className) || !is_subclass_of($className, Module::class)) {
            return null;
        }

        return Me::createObject(['class' => $className, 'id' => $id, 'parent' => $this]);
    }
}