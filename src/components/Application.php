<?php
namespace me\components;
use Me;
use Exception;
/**
 * 
 */
class Application extends Component {
    /**
     * @var string
     */
    public $charset          = 'UTF-8';
    /**
     * @var string
     */
    public $language         = 'fa-IR';
    /**
     * @var string
     */
    public $timezone         = 'Asia/Tehran';
    /**
     * @var string
     */
    public $moduleNamespace  = 'app\Modules';
    /**
     * 
     */
    private $_definitions    = [];
    /**
     * 
     */
    private $_components     = [];
    /**
     * @var array
     */
    private $_corecomponents = [
        'routeManager' => ['class' => 'me\components\RouteManager'],
        'request'      => ['class' => 'me\components\Request'],
        'response'     => ['class' => 'me\components\Response'],
    ];
    /**
     * 
     */
    public function __construct($config = []) {
        if (!isset($config['components'])) {
            $config['components'] = [];
        }
        $config['components'] = array_replace_recursive($this->_corecomponents, $config['components']);
        parent::__construct($config);
    }
    /**
     * @return void
     */
    public function init() {
        parent::init();
        Me::$app = $this;
    }
    /**
     * 
     */
    public function get($id) {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }
        
        if (isset($this->_definitions[$id])) {
            return $this->_components[$id] = Me::createObject($this->_definitions[$id]);
        }
        
        throw new Exception("Component { $id } Not Found", 11001);
    }
    /**
     * 
     */
    public function set($id, $definition = null) {
        unset($this->_components[$id]);
        if ($definition === null) {
            unset($this->_definitions[$id]);
            return;
        }
        $this->_definitions[$id] = $definition;
    }
    /**
     * @param array $components
     * @return void
     */
    public function setComponents($components) {
        foreach ($components as $id => $definition) {
            $this->set($id, $definition);
        }
    }
    /**
     * @param string $basePath
     * @return void
     */
    public function setBasePath($basePath) {
        Me::setAlias('@app', $basePath);
    }
    /**
     * @return void
     */
    public function run() {
        set_exception_handler(function ($exc) {
            $code      = $exc->getCode();
            $file      = $exc->getFile();
            $line      = $exc->getLine();
            $exception = get_class($exc);
            $message   = $exc->getMessage();
            $this->handle_error($code, $file, $line, $message, $exception);
        });
        set_error_handler(function ($code, $message, $file, $line) {
            $this->handle_error($code, $file, $line, $message);
        });
        $this->handle_request();
    }
    /**
     * 
     */
    public function handle_error($code, $file, $line, $message, $exception = null) {
        /* @var $response Response */
        $data = ['done' => false, 'messages' => ['خطای سرور']];
        if (ME_DEBUG) {
            $data['messages'] = [$message];
            $data['code'] = $code;
            $data['file'] = $file;
            $data['line'] = $line;
            if (!is_null($exception)) {
                $data['exception'] = $exception;
            }
        }
        $response       = $this->get('response');
        $response->data = $data;
        $response->send();
    }
    /**
     * 
     */
    public function handle_request() {
        list($route, $params) = $this->get('request')->resolve();
        $result = $this->handle_action($route, $params);
        if ($result instanceof Response) {
            $result->send();
        }

        $response       = $this->get('response');
        $response->data = ['done' => true, 'data' => $result];
        $response->send();
    }
    /**
     * @param string $route
     * @param array $params
     * @return Response|string
     */
    public function handle_action($route, $params) {
        /* @var $module Module */
        /* @var $controller Controller */
        /* @var $actionID string */
        list($module, $route2) = $this->create_module($route);
        list($controller, $actionID) = $module->create_controller($route2);
        return $controller->run_action($actionID, $params);
    }
    /**
     * 
     */
    public function create_module($route) {
        list($id, $route2) = explode('/', $route, 2);

        $name      = str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
        $className = $this->moduleNamespace . "\\$name\\Module";

        if (!class_exists($className) || !is_subclass_of($className, 'me\components\Module')) {
            throw new Exception("Module { $className } Not Found", 11002);
        }

        $module = Me::createObject(['class' => $className, 'id' => $id, 'parent' => null]);

        return [$module, $route2];
    }
}