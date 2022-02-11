<?php
namespace me\helpers;
use stdClass;
use me\model\Model;
class JsonHelper {
    public static function encode($value) {
        $expressions = [];
        $value       = static::processData($value, $expressions, uniqid('', true));
        $json        = json_encode($value);
        return $expressions === [] ? $json : strtr($json, $expressions);
    }
    protected static function processData($data, &$expressions, $expPrefix) {
        if (is_object($data)) {

            /*
              if ($data instanceof JsExpression) {
              $token                           = "!{[$expPrefix=" . count($expressions) . ']}!';
              $expressions['"' . $token . '"'] = $data->expression;

              return $token;
              }
              if ($data instanceof \JsonSerializable) {
              return static::processData($data->jsonSerialize(), $expressions, $expPrefix);
              }
              if ($data instanceof \DateTimeInterface) {
              return static::processData((array) $data, $expressions, $expPrefix);
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
                    $data[$key] = static::processData($value, $expressions, $expPrefix);
                }
            }
        }
        return $data;
    }
}