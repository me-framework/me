<?php
namespace me\components;
use Me;
use Exception;
use me\core\Component;
use me\url\UrlManager;
use me\schema\SchemaManager;
use me\database\DatabaseManager;
/**
 * 
 */
class Application extends Component {
    /**
     * @var string Charset
     */
    public $charset          = 'UTF-8';
    /**
     * @var string Language
     */
    public $language         = 'fa-IR';
    /**
     * @var string Time Zone
     */
    public $timezone         = 'Asia/Tehran';
    /**
     * @var string Module Namespace
     */
    public $moduleNamespace  = 'app\modules';
    /**
     * @var string Default Route
     */
    public $defaultRoute     = 'site/default/index';
    /**
     * @var array Components Definitions
     */
    private $_definitions    = [];
    /**
     * @var array Components Objects
     */
    private $_components     = [];
    /**
     * @var array Core Components
     */
    private $_corecomponents = [
        'url'      => ['class' => UrlManager::class],
        'request'  => ['class' => Request::class],
        'response' => ['class' => Response::class],
        'schema'   => ['class' => SchemaManager::class],
        'database' => ['class' => DatabaseManager::class],
    ];
    /**
     * @param array $config Application Config
     * @return \me\components\Application
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
     * @param array $components Components Definitions
     * @return void
     */
    public function setComponents($components) {
        foreach ($components as $id => $definition) {
            $this->set($id, $definition);
        }
    }
    /**
     * @param string $basePath Base Path
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
     * @return void
     */
    public function handle_error($code, $file, $line, $message, $exception = null) {
        $data = ['done' => false, 'messages' => ['خطای سرور']];
        if (ME_DEBUG) {
            $data['messages'] = [$message];
            $data['code']     = $code;
            $data['file']     = $file;
            $data['line']     = $line;
            if (!is_null($exception)) {
                $data['exception'] = $exception;
            }
        }
        /* @var $response \me\components\Response */
        $response       = $this->get('response');
        $response->data = $data;
        $response->send();
    }
    /**
     * @return void
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
     * @param string $route Route
     * @param array $params Parameters
     * @return \me\components\Response|mixed
     */
    public function handle_action($route, $params) {
        /* @var $module \me\components\Module */
        /* @var $controller \me\components\Controller */
        /* @var $action_id string */
        list($module, $route2) = $this->create_module($route);
        list($controller, $action_id) = $module->create_controller($route2);
        return $controller->run_action($action_id, $params);
    }
    /**
     * @param string $route Route
     * @return array [\me\components\Module $module, string $route]
     */
    public function create_module($route) {

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

        $name      = str_replace('-', '_', strtolower($id));
        $className = $this->moduleNamespace . "\\$name\\module";

        if (!class_exists($className) || !is_subclass_of($className, Module::class)) {
            throw new Exception("Module { $className } Not Found", 11002);
        }

        $module = Me::createObject(['class' => $className, 'id' => $id, 'parent' => null]);

        return [$module, $route2];
    }
}