<?php
namespace me\components;
use Me;
use me\url\UrlManager;
use me\core\Component;
use me\core\Container;
use me\helpers\ArrayHelper;
use me\helpers\StringHelper;
use me\exceptions\Exception;
use me\database\DatabaseManager;
/**
 * @property-read \me\url\UrlManager $urlManager Url Manager
 * @property-read \me\components\Request $request Request
 * @property-read \me\components\Response $response Response
 * @property-read \me\database\DatabaseManager $database Database Manager
 */
class Application extends Component {
    /**
     * @var string Module Namespace
     */
    public $moduleNamespace    = 'app\modules';
    /**
     * @var string Default Route
     */
    public $defaultRoute       = 'site/default/index';
    /**
     * @var string Time Zone
     */
    protected $_timezone       = 'Asia/Tehran';
    /**
     * @var array Components Objects
     */
    protected $_components     = [];
    /**
     * @var array Components Definitions
     */
    protected $_definitions    = [];
    /**
     * @var array Core Components
     */
    protected $_coreComponents = [
        'url'      => ['class' => UrlManager::class],
        'request'  => ['class' => Request::class],
        'response' => ['class' => Response::class],
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
        $config['components'] = array_replace_recursive($this->_coreComponents, $config['components']);
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
            $this->handleError($code, $file, $line, $message);
        });
        set_error_handler(function ($code, $message, $file, $line) {
            $this->handleError($code, $file, $line, $message);
        });
        $this->handleRequest();
    }
    /**
     * 
     */
    public function get($id) {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }
        if (isset($this->_definitions[$id])) {
            $config = $this->_definitions[$id];
            $class = ArrayHelper::Remove($config, 'class');
            return $this->_components[$id] = Container::build($class, $config);
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
     * @return \me\components\Request Request
     */
    public function getRequest() {
        return $this->get('request');
    }
    /**
     * @return \me\components\Response Response
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
     * @param string $timezone timezone
     * @return void
     */
    public function setTimezone($timezone) {
        $this->_timezone = $timezone;
        date_default_timezone_set($timezone);
    }
    /**
     * 
     */
    public function setBasePath($basePath) {
        Me::set_alias('@app', $basePath);
    }
    /**
     * @return void
     */
    private function handleError($code, $file, $line, $message) {
        $data = ['code' => $code, 'message' => 'خطای سرور'];
        if (ME_DEBUG) {
            $data['file']    = $file;
            $data['line']    = $line;
            $data['message'] = $message;
        }
        $response       = $this->getResponse();
        $response->code = 500;
        $response->data = $data;
        $response->send();
    }
    /**
     * @return void
     */
    private function handleRequest() {
        [$route, $params] = $this->getRequest()->resolve();

        $data = $this->handleAction($route, $params);
        if ($data instanceof Response) {
            $data->send();
        }

        $response       = $this->getResponse();
        $response->data = $data;
        $response->send();
    }
    /**
     * @param string $module_id Route
     * @param array $params Parameters
     * @return \me\components\Response|mixed
     */
    private function handleAction($module_id, $params) {
        /* @var $module     \me\components\Module */
        [$module, $controller_id] = $this->createModule($module_id);

        /* @var $controller \me\components\Controller */
        [$controller, $action_id] = $module->createController($controller_id);

        return $controller->runAction($action_id, $params);
    }
    /**
     * @param string $module_id Route
     * @return array [\me\components\Module $module, string $route]
     */
    private function createModule($module_id) {
        if ($module_id === '') {
            $module_id = $this->defaultRoute;
        }

        $id    = $module_id;
        $route = '';
        if (strpos($module_id, '/') !== false) {
            [$id, $route] = explode('/', $module_id, 2);
        }

        $name      = StringHelper::id2name($id);
        $className = $this->moduleNamespace . "\\$name\\module";
        if (!class_exists($className)) { //  || !($className instanceof Module)
            throw new Exception("Module { $className } Not Found", 11002);
        }

        $module = Container::build($className, ['id' => $id]);
        return [$module, $route];
    }
}