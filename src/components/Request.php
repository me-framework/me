<?php
namespace me\components;
use Me;
use Exception;
use me\core\Component;
/**
 * @property-read string $pathInfo Path Info
 * @property-read string $scriptUrl Script Url
 * @property-read string $baseUrl Base Url
 */
class Request extends Component {
    /**
     * @var string Path Info
     */
    private $_pathInfo;
    /**
     * @var string Script Url
     */
    private $_scriptUrl;
    /**
     * @var string Base Url
     */
    private $_baseUrl;
    /**
     * @return array [string $route, array $params]
     */
    public function resolve() {
        /* @var $urlManager \me\url\UrlManager */
        $urlManager = Me::$app->get('urlManager');
        $pathInfo   = $this->getPathInfo();
        $method     = $this->getMethod();
        $result     = $urlManager->parseRequest($pathInfo, $method);
        if ($result === false) {
            throw new Exception('Page Not Found');
        }
        list($route, $params) = $result;
        $_GET = $params + $_GET;
        return [$route, $this->get()];
    }
    /**
     * @return string Path Info
     */
    public function getPathInfo() {
        if ($this->_pathInfo === null) {
            $this->_pathInfo = $this->resolvePathInfo();
        }
        return $this->_pathInfo;
    }
    /**
     * @return string Path Info
     */
    public function resolvePathInfo() {
        $pathInfo = filter_input(INPUT_SERVER, 'REQUEST_URI');
        if (($pos      = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }
        $baseUrl = $this->getBaseUrl();
        if ($baseUrl !== '' && strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        }
        return trim($pathInfo, '/');
    }
    /**
     * @return string Script Url
     */
    public function getScriptUrl() {
        if ($this->_scriptUrl === null) {
            $this->_scriptUrl = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
        }
        return $this->_scriptUrl;
    }
    /**
     * @return string Base Url
     */
    public function getBaseUrl() {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }
        return $this->_baseUrl;
    }
    /**
     * @return string Request Method
     */
    public function getMethod() {
        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
        if ($method) {
            return strtoupper($method);
        }
        return 'GET';
    }
    public function getIsGet() {
        return $this->getMethod() === 'GET';
    }
    public function getIsOptions() {
        return $this->getMethod() === 'OPTIONS';
    }
    public function getIsHead() {
        return $this->getMethod() === 'HEAD';
    }
    public function getIsPost() {
        return $this->getMethod() === 'POST';
    }
    public function getIsDelete() {
        return $this->getMethod() === 'DELETE';
    }
    public function getIsPut() {
        return $this->getMethod() === 'PUT';
    }
    public function getIsPatch() {
        return $this->getMethod() === 'PATCH';
    }
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
}