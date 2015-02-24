<?php

class Service
{
    public static function parsePost()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return false;
        }
        if (!empty($_POST)) {
            return false;
        }
        $data = json_decode(file_get_contents('php://input'), true, 8);
        if (is_array($data)) {
            $_POST = $data;
        }
        return true;
    }

    public static function checkRequest($requestSalt)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return false;
        }

        if (empty($_POST['sign'])) {
            self::log('warn', 'Request without sign', $_POST);
            return false;
        }
        if (empty($_POST['request'])) {
            self::log('warn', 'Empty request', $_POST);
            return false;
        } else {
            $_POST['request'] = urldecode($_POST['request']);
        }
        
        if ($_POST['sign'] != md5($_POST['request'] . $requestSalt)) {
            self::log('warn', 'Request with wrong sign', $_POST);
            return false;
        }

        $_POST['request'] = json_decode($_POST['request'], true);
        return true;
    }

    public static function sendRequest($url, array $request, $requestSalt)
    {
        $request = json_encode($request, JSON_UNESCAPED_UNICODE);
        $sign = md5($request . $requestSalt);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'request' => $request,
                'sign' => $sign
            ]
        ]);
        $response = curl_exec($curl);
        self::log('info', 'Got response: "' . $response . '"');
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($info['http_code'] != 200) {
            return ['success' => false, 'message' => "Http code $info[http_code]"];
        }

        return json_decode($response, true);
    }

    public static function log($level, $message)
    {
        $args = func_get_args();
        array_shift($args);
        $result = date('[c] ');
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $result .= print_r($arg, true) . "\n";
            } else {
                $result .= $arg . "\n";
            }
        }

        file_put_contents(dirname(__DIR__) . "/runtime/logs/$level.log", trim($result) . "\n", FILE_APPEND);
    }
}