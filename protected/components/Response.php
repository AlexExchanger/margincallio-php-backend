<?php

class Response extends CComponent {

    const RESPONSE_ERROR = 0;
    const RESPONSE_SUCCESS = 1;
    const RESPONSE_NOT_FOUND = 2;


    public static function GetResponseSuccess($data=array(), $message='') {
        return CJSON::encode(array(
            'status' => self::RESPONSE_SUCCESS,
            'data' => $data,
            'message' => $message
        ));
    }

    public static function GetResponseError($message=''){
        return CJSON::encode(array(
            'status' => self::RESPONSE_ERROR,
            'message' => $message
        ));
    }
    
    public static function ResponseSuccess($data=array(), $message='') {
        header('Content-Type: application/json');
        print self::GetResponseSuccess($data, $message);
        exit();
    }

    public static function ResponseError($message=''){
        header('Content-Type: application/json');
        print self::GetResponseError($message);
        exit();
    }
}