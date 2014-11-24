<?php

class StringGenerator
{
    public static function generateAlphaNumeric($len)
    {
        //[0-9] + [a-z] + [A-Z]
        $symbols = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        $code = '';
        for ($i = 0; $i < $len; $i++) {
            $code .= $symbols[array_rand($symbols, 1)];
        }
        return $code;
    }

    public static function validateUserPublicId($string)
    {
        return preg_match('`^U[0-9A-Fa-f]+$`', $string);
    }

    public static function validateGatewayPublicId($string)
    {
        return preg_match('`^(USD|BTC)\-G[0-9A-Fa-f]+\-A[0-9A-Fa-f]+$`', $string);
    }

    public static function validateAccountPublicId($string)
    {
        return preg_match('`^(USD|BTC)\-U[0-9A-Fa-f]+\-A[0-9A-Fa-f]+$`', $string);
    }

    public static function generateApiUserId()
    {
        $len = 20;
        $chars = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        $code = '';
        while (strlen($code) < $len) {
            $code .= $chars[array_rand($chars)];
        }
        return $code;
    }
}