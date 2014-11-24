<?php
namespace ext\engine;

class Engine
{
    public
        $host,
        $port;

    function init()
    {

    }

    function createOrder($orderID, $ticker, $side, $type, $price, $size, $rest, $accountFromId, $accountToId, $operationId, $brokerId)
    {
        return $this->send([
            'orderId' => $orderID,
            'status' => 'pendingAccepted',
            'ticker' => $ticker,
            'side' => $side,
            'type' => $type,
            'price' => $price,
            'size' => $size,
            'rest' => $rest,
            'accountFromId' => $accountFromId,
            'accountToId' => $accountToId,
            'operationId' => $operationId,
            'brokerId' => $brokerId
        ]);
    }

    function cancelOrder($orderID, $ticker, $side, $type = '', $price = 0., $size = 0., $accountFromId = '', $accountToId = '')
    {
        return $this->send([
            'orderId' => $orderID,
            'status' => 'pendingCancelled',
            'ticker' => $ticker,
            'side' => $side,
            'type' => $type,
            'price' => $price,
            'size' => $size,
            'accountFromId' => $accountFromId,
            'accountToId' => $accountToId
        ]);
    }

    public function send(array $request = array())
    {

        $fp = @fsockopen($this->host, $this->port, $errno, $errstr, 2);
        if (!$fp) {
            throw new \ModelException("Error ($errno): $errstr", ['host' => $this->host, 'port' => $this->port, 'request' => $request]);
        }

        $content = json_encode($request, JSON_UNESCAPED_UNICODE);

        $headers = [
            'POST / HTTP/1.1',
            "Host: $this->host",
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($content),
            'Connection: close'
        ];

        fwrite($fp, join("\r\n", $headers) . "\r\n\r\n");
        fwrite($fp, $content);

        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 1024);
        }

        $response = explode("\r\n\r\n", $response, 2);
        $response = !empty($response[1]) ? $response[1] : '';

        $json = json_decode($response, true);
        if (!is_array($json)) {
            throw new \ModelException("Wrong response: $response");
        }

        return $json;
    }
}