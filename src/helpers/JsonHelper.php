<?php
namespace me\helpers;
use stdClass;
use Exception;
use me\model\Model;
class JsonHelper {
    public static function encode($value) {
        $array = static::processData($value);
        $json  = json_encode($array);
        return $json;
    }
    public static function decode($json, $asArray = true) {
        if (is_array($json)) {
            throw new Exception('Invalid JSON data.');
        }
        elseif ($json === null || $json === '') {
            return null;
        }
        $decode = json_decode((string) $json, $asArray);
        return $decode;
    }
    protected static function processData($data) {
        if (is_object($data)) {
            /*
              if ($data instanceof \JsonSerializable) {
              return static::processData($data->jsonSerialize());
              }
              if ($data instanceof \DateTimeInterface) {
              return static::processData((array) $data);
              }
             */
            //if ($data instanceof \SimpleXMLElement) {
            //    $data = (array) $data;
            //}
            //else 
            if ($data instanceof Model) {
                $data = $data->toArray();
            }
            else {
                $result = [];
                foreach ($data as $name => $value) {
                    $result[$name] = $value;
                }
                $data = $result;
            }
            if ($data === []) {
                return new stdClass();
            }
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = static::processData($value);
                }
            }
        }
        return $data;
    }
}