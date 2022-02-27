<?php
namespace me\components;
use Exception;
use me\core\Component;
use me\core\components\Container;
/**
 * 
 */
class module extends Component {
    /**
     * @var string Module ID
     */
    public $id;
    /**
     * @var \me\components\module|null Parent Object or null (root module)
     */
    public $parent;
    /**
     * @var string Controller Namespace
     */
    public $controller_namespace;
    /**
     * @var string Module Namespace
     */
    public $module_namespace;
    /**
     * @var string Default Route
     */
    public $default_route = 'default/index';
    /**
     * @param string $route Route
     * @return array [\me\components\controller $controller, string $action_id]
     */
    public function create_controller($route) {

        if ($route === '') {
            $route = $this->default_route;
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

        $name      = str_replace('-', '_', strtolower($id)) . '_controller';
        $className = "$this->controller_namespace\\$name";
        if (!class_exists($className) || !is_subclass_of($className, controller::class)) {
            throw new Exception("Controller { $className } Not Found", 12001);
        }

        $controller = Container::build(['class' => $className, 'id' => $id, 'parent' => $this]);
        return [$controller, $route2];
    }
    /**
     * @param string $id Module ID
     * @return \me\components\module|null Module Object or null
     */
    public function get_module($id) {

        $name      = str_replace('-', '_', strtolower($id));
        $className = $this->module_namespace . "\\$name\\module";

        if (!class_exists($className) || !is_subclass_of($className, module::class)) {
            return null;
        }

        return Container::build(['class' => $className, 'id' => $id, 'parent' => $this]);
    }
}