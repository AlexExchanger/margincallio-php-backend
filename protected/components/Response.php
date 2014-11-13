<?php

class Response extends CComponent {

    const RESPONSE_ERROR = 0;
    const RESPONSE_SUCCESS = 1;
    const RESPONSE_NOT_FOUND = 2;


    public static function ResponseSuccess($data=array(), $message='') {
        return CJSON::encode(array(
            'status' => self::RESPONSE_SUCCESS,
            'data' => $data,
            'message' => $message
        ));
    }

    public static function ResponseError($message=''){
        return CJSON::encode(array(
            'status' => self::RESPONSE_ERROR,
            'message' => $message
        ));
    }

}