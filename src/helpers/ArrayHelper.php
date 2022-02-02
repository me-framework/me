<?php
namespace me\helpers;
class ArrayHelper extends Helper {
    /**
     * @param array $array Array
     * @param mixed $name Array Key
     * @param mixed $defaultValue Default Value
     * @return mixed Array Value Or Default Value
     */
    public static function Remove(array &$array, $name, $defaultValue = null) {
        if (isset($array[$name])) {
            $data = $array[$name];
            unset($array[$name]);
            return $data;
        }
        return $defaultValue;
    }
    /**
     * @param array $array1 The array in which elements are replaced.
     * @param array $array2 The array from which elements will be extracted.
     * @return array|null an array, or NULL if an error occurs.
     */
    public static function Extend() {
        return call_user_func_array('array_replace_recursive', func_get_args());
    }
    /**
     * @return bool
     */
    public static function isAssociative($array, $allStrings = true) {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }
            return true;
        }
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }
        return false;
    }
    public static function isTraversable($var) {
        return is_array($var) || $var instanceof \Traversable;
    }
    public static function AddIfNotExist(array &$array, $key, $value) {
        if (!isset($array[$key])) {
            $array[$key] = $value;
        }
    }
    public static function isIn($needle, $haystack, $strict = false) {
        if ($haystack instanceof \Traversable) {
            foreach ($haystack as $value) {
                if ($needle == $value && (!$strict || $needle === $value)) {
                    return true;
                }
            }
        }
        elseif (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        }
        else {
            throw new \Exception('Argument $haystack must be an array or implement Traversable');
        }

        return false;
    }
}