<?php

class Response extends CComponent {

    const RESPONSE_ERROR = 0;
    const RESPONSE_SUCCESS = 1;
    const RESPONSE_NOT_FOUND = 2;

    
    public static function setHeaders() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: '.implode(' ', Yii::app()->params['allowDomains']));
    }
    
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
        self::setHeaders();
        print self::GetResponseSuccess($data, $message);
        exit();
    }

    public static function ResponseError($message=''){
        self::setHeaders();
        print self::GetResponseError($message);
        exit();
    }
}