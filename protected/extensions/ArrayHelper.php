<?php

class ArrayHelper
{
    static function getFromArray($array, $key, $default = null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    public static function objectMap($object, array $fields = [], array $additionalFields = [])
    {
        $return = [];
        foreach ($fields as $k => $v) {
            if (is_int($k)) {
                $k = $v;
            }
            $return[$k] = $object->$v;
        }

        foreach ($additionalFields as $k => $v) {
            $return[$k] = $v;
        }
        return $return;
    }
}