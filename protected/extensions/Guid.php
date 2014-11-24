<?php

class Guid
{
    public static function generate()
    {
        $uid = uniqid(mt_rand(), true);
        $data = microtime();
        $data .= mt_rand();
        $data .= !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $data .= !empty($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : '';
        $data .= !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $data .= !empty($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : '';
        $hash = hash('ripemd128', $uid . md5($data));
        $guid = substr($hash, 0, 8) .
            '-' . substr($hash, 8, 4) .
            '-' . substr($hash, 12, 4) .
            '-' . substr($hash, 16, 4) .
            '-' . substr($hash, 20, 12);
        return $guid;
    }

    public static function validate($string)
    {
        return (bool)preg_match('~^[a-f\d]{8}-[a-f\d]{4}-[a-f\d]{4}-[a-f\d]{4}-[a-f\d]{12}$~', $string);
    }
}