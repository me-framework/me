<?php
namespace me\components;
use Me;
use Exception;
use me\url\UrlManager;
use me\core\Component;
use me\core\Container;
use me\database\DatabaseManager;
/**
 * @property-read \me\url\UrlManager $urlManager Url Manager
 * @property-read \me\components\request $request Request
 * @property-read \me\components\response $response Response
 * @property-read \me\database\DatabaseManager $database Database Manager
 */
class application extends Component {
    /**
     * @var string Charset
     */
    public $charset             = 'UTF-8';
    /**
     * @var string Language
     */
    public $language            = 'fa-IR';
    /**
     * @var string Time Zone
     */
    public $timezone            = 'Asia/Tehran';
    /**
     * @var string Module Namespace
     */
    public $module_namespace    = 'app\modules';
    /**
     * @var string Default Route
     */
    public $default_route       = 'site/default/index';
    /**
     * @var array Components Definitions
     */
    protected $_definitions     = [];
    /**
     * @var array Components Objects
     */
    protected $_components      = [];
    /**
     * @var array Core Components
     */
    protected $_core_components = [
        'url'      => ['class' => UrlManager::class],
        'request'  => ['class' => request::class],
        'response' => ['class' => response::class],
        'database' => ['class' => DatabaseManager::class],
    ];
    /**
     * @param array $config Application Config
     * @return \me\components\application
     */
    public function __construct($config = []) {
        if (!isset($config['components'])) {
            $config['components'] = [];
        }
        $config['components'] = array_replace_recursive($this->_core_components, $config['components']);
        parent::__construct($config);
    }
    /**
     * @return void
     */
    protected function init() {
        parent::init();
        Me::$app = $this;
    }
    /**
     * @return void
     */
    public function run() {
        set_exception_handler(function ($exc) {
            $code    = $exc->getCode();
            $file    = $exc->getFile();
            $line    = $exc->getLine();
            $message = $exc->getMessage();
            $this->handle_error($code, $file, $line, $message);
        });
        set_error_handler(function ($code, $message, $file, $line) {
            $this->handle_error($code, $file, $line, $message);
        });
        $this->handle_request();
    }
    /**
     * 
     */
    public function get($id) {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }
        if (isset($this->_definitions[$id])) {
            return $this->_components[$id] = Container::build($this->_definitions[$id]);
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
     * @return \me\url\UrlManager Url Manager
     */
    public function getUrlManager() {
        return $this->get('url');
    }
    /**
     * @return \me\components\request Request
     */
    public function getRequest() {
        return $this->get('request');
    }
    /**
     * @return \me\components\response Response
     */
    public function getResponse() {
        return $this->get('response');
    }
    /**
     * @return \me\database\DatabaseManager Database Manager
     */
    public function getDatabase() {
        return $this->get('database');
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
     * @param string $base_path Base Path
     * @return void
     */
    public function setBase_path($base_path) {
        Me::set_alias('@app', $base_path);
    }
    /**
     * @return void
     */
    private function handle_error($code, $file, $line, $message) {
        $data = ['done' => false, 'code' => $code, 'message' => 'خطای سرور'];
        if (ME_DEBUG) {
            $data['message'] = $message;
            $data['file']    = $file;
            $data['line']    = $line;
        }
        $response       = $this->getResponse();
        $response->data = $data;
        $response->send();
    }
    /**
     * @return void
     */
    private function handle_request() {
        [$route, $params] = $this->getRequest()->resolve();

        $data = $this->handle_action($route, $params);
        if ($data instanceof response) {
            $data->send();
        }

        $response       = $this->getResponse();
        $response->data = ['done' => true, 'data' => $data];
        $response->send();
    }
    /**
     * @param string $route Route
     * @param array $params Parameters
     * @return \me\components\response|mixed
     */
    private function handle_action($module_id, $params) {
        /* @var $module     \me\components\module */
        /* @var $controller \me\components\controller */
        [$module, $controller_id] = $this->create_module($module_id);
        [$controller, $action_id] = $module->create_controller($controller_id);
        return $controller->run_action($action_id, $params);
    }
    /**
     * @param string $module_id Route
     * @return array [\me\components\module $module, string $route]
     */
    private function create_module($module_id) {
        if ($module_id === '') {
            $module_id = $this->default_route;
        }

        $id    = $module_id;
        $route = '';
        if (strpos($module_id, '/') !== false) {
            [$id, $route] = explode('/', $module_id, 2);
        }

        $name      = str_replace('-', '_', strtolower($id));
        $className = $this->module_namespace . "\\$name\\module";
        if (!class_exists($className)) { //  || !($className instanceof module)
            throw new Exception("Module { $className } Not Found", 11002);
        }

        $module = Container::build(['class' => $className, 'id' => $id]);
        return [$module, $route];
    }
}