<?php
namespace me\components;
use Exception;
use me\core\Component;
use me\core\Container;
/**
 * 
 */
class module extends Component {
    /**
     * @var string Module ID
     */
    public $id;
    /**
     * @var string Default Route
     */
    public $default_route = 'default/index';
    /**
     * @var string Controller Namespace
     */
    public $controller_namespace;
    /**
     * @var string Module Namespace
     */
    public $module_namespace;
    /**
     * @var \me\components\module|null Parent Object or null (root module)
     */
    public $parent;
    /**
     * @param string $controller_id Route
     * @return array [\me\components\controller $controller, string $action_id]
     */
    public function create_controller($controller_id) {
        if ($controller_id === '') {
            $controller_id = $this->default_route;
        }

        $id     = $controller_id;
        $route = '';
        if (strpos($controller_id, '/') !== false) {
            [$id, $route] = explode('/', $controller_id, 2);
        }

        $module = $this->get_module($id);
        if ($module !== null) {
            return $module->create_controller($route);
        }

        $name      = str_replace('-', '_', strtolower($id));
        $className = $this->controller_namespace . "\\{$name}_controller";
        if (!class_exists($className)) { //  || !($className instanceof controller)
            throw new Exception("Controller { $className } Not Found", 12001);
        }

        $controller = Container::build(['class' => $className, 'id' => $id, 'parent' => $this]);
        return [$controller, $route];
    }
    /**
     * @param string $id Module ID
     * @return \me\components\module|null Module Object or null
     */
    public function get_module($id) {
        $name      = str_replace('-', '_', strtolower($id));
        $className = $this->module_namespace . "\\$name\\module";
        if (!class_exists($className) || !($className instanceof module)) {
            return null;
        }
        return Container::build(['class' => $className, 'id' => $id, 'parent' => $this]);
    }
}