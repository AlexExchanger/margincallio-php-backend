<?php

class Response extends CComponent {

    const RESPONSE_ERROR = 0;
    const RESPONSE_SUCCESS = 1;
    const RESPONSE_NOT_FOUND = 2;

    
    public static function setHeaders() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Credentials: true');
        if(isset($_SERVER['HTTP_ORIGIN'])) {
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
        }
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
    
    public static function ResponseAccessDenied($message='Access denied'){
        self::setHeaders();
        http_response_code(403);
        print self::GetResponseError($message);
        exit();
    }
    
    public static function bcScaleOut($value, $accuracy=6) {
        bcscale($accuracy);
        $result = bcmul($value, 1);
        bcscale(15);
        return $result;
    }
    
}