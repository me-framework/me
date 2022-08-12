<?php
defined('ME_DEBUG') || define('ME_DEBUG', false);
define('ME_PATH', __DIR__ . '/src');
define('ROOT_PATH', dirname(filter_input(INPUT_SERVER, 'SCRIPT_FILENAME')));
define('WEB', dirname(filter_input(INPUT_SERVER, 'PHP_SELF')));
class Me {
    /**
     * @var \me\components\Application
     */
    public static $app;
    /**
     * 
     */
    public static function autoload($class_name) {
        if (strpos($class_name, 'app') === false) {
            return;
        }
        $file = static::get_alias('@' . str_replace(['\\'], ['/'], $class_name) . '.php');
        if (!is_file($file)) {
            return;
        }
        include $file;
    }
    /**
     * @var array
     */
    public static $aliases = [
        '@me'   => ME_PATH,
        '@root' => ROOT_PATH,
        '@web'  => WEB,
    ];
    /**
     * 
     */
    public static function get_alias($alias) {
        if (substr($alias, 0, 1) !== '@') {
            return $alias;
        }
        $root = $alias;
        $path = '';
        if (($pos  = strpos($alias, '/')) !== false) {
            $root = substr($alias, 0, $pos);
            $path = substr($alias, $pos);
        }
        if (isset(static::$aliases[$root])) {
            return static::get_alias(static::$aliases[$root] . $path);
        }
        return null;
    }
    /**
     * 
     */
    public static function set_alias($alias, $path) {
        static::$aliases[$alias] = $path;
    }
}
spl_autoload_register(['Me', 'autoload'], true, true);
