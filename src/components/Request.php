<?php
namespace me\components;
use Me;
/**
 * @property-read string $pathInfo
 * @property-read string $scriptUrl
 * @property-read string $baseUrl
 */
class Request extends Component {
    /**
     * 
     */
    private $_pathInfo;
    /**
     * 
     */
    private $_scriptUrl;
    /**
     * 
     */
    private $_baseUrl;
    public function get($name = null, $defaultValue = null) {
        if ($name === null) {
            return $_GET;
        }
        $value = filter_input(INPUT_GET, $name);
        if (is_null($value)) {
            return $defaultValue;
        }
        return $value;
    }
    public function post($name = null, $defaultValue = null) {
        if ($name === null) {
            return $_POST;
        }
        $value = filter_input(INPUT_POST, $name);
        if (is_null($value)) {
            return $defaultValue;
        }
        return $value;
    }
    public function files($name = null, $defaultValue = null) {
        if ($name === null) {
            return $_FILES;
        }
        if (isset($_FILES[$name])) {
            return $_FILES[$name];
        }
        return $defaultValue;
    }
    /**
     * @return array
     */
    public function resolve() {
        list($route, $params) = Me::$app->get('routeManager')->parseRequest($this);
        return [$route, $params];
    }
    /**
     * 
     */
    public function getPathInfo() {
        if ($this->_pathInfo === null) {
            $this->_pathInfo = $this->resolvePathInfo();
        }
        return $this->_pathInfo;
    }
    /**
     * 
     */
    public function resolvePathInfo() {
        $pathInfo = filter_input(INPUT_SERVER, 'REQUEST_URI');
        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }
        $baseUrl = $this->getBaseUrl();
        if ($baseUrl !== '' && strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        }
        return $pathInfo;
    }
    /**
     * 
     */
    public function getScriptUrl() {
        if ($this->_scriptUrl === null) {
            $this->_scriptUrl = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
        }
        return $this->_scriptUrl;
    }
    /**
     * 
     */
    public function getBaseUrl() {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }
        return $this->_baseUrl;
    }
    /**
     * 
     */
    public function getMethod(): string {
        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
        if ($method) {
            return strtoupper($method);
        }
        return 'GET';
    }
    public function getIsGet(): bool {
        return $this->getMethod() === 'GET';
    }
    public function getIsOptions(): bool {
        return $this->getMethod() === 'OPTIONS';
    }
    public function getIsHead(): bool {
        return $this->getMethod() === 'HEAD';
    }
    public function getIsPost(): bool {
        return $this->getMethod() === 'POST';
    }
    public function getIsDelete(): bool {
        return $this->getMethod() === 'DELETE';
    }
    public function getIsPut(): bool {
        return $this->getMethod() === 'PUT';
    }
    public function getIsPatch(): bool {
        return $this->getMethod() === 'PATCH';
    }
}