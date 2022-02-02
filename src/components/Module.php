<?php
namespace me\components;
use Me;
use Exception;
/**
 * 
 */
class Module extends Component {
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
    public $controllerNamespace;
    /**
     * @var string
     */
    public $moduleNamespace;
    /**
     * @param string $route
     * @return array
     */
    public function create_controller($route) {
        list($id, $route2) = explode('/', $route, 2);

        $module = $this->get_module($id);
        if ($module !== null) {
            return $module->create_controller($route2);
        }

        $name      = str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
        $className = $this->controllerNamespace . '\\' . $name . 'Controller';
        if (!class_exists($className) || !is_subclass_of($className, Controller::class)) {
            throw new Exception("Controller { $className } Not Found", 12001);
        }

        $controller = Me::createObject(['class' => $className, 'id' => $id, 'parent' => $this]);
        return [$controller, $route2];
    }
    /**
     * @return Module
     */
    public function get_module($id) {

        $name      = str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
        $className = $this->moduleNamespace . "\\$name\\Module";

        if (!class_exists($className) || !is_subclass_of($className, Module::class)) {
            return null;
        }

        return Me::createObject(['class' => $className, 'id' => $id, 'parent' => $this]);
    }
}