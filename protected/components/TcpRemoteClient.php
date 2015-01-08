<?php

class TcpRemoteClient extends CComponent {

    private $_url;
    private $_port;
    private $_isConnetctionEstablished = false;


    private $_tcpResource;

    //error fields
    private $_errno;
    private $_errstr;

    //Settings
    const TCP_CONNECTION_TIMEOUT = 10;

    //Remote funcitons
    const FUNC_CREATE_TRADE_ACCOUNT = 100;
    const FUNC_LOCK_TRADE_ACCOUNT = 200;
    const FUNC_UNLOCK_TRADE_ACCOUNT = 300;
    const FUNC_REMOVE_TRADE_ACCOUNT = 400;
    const FUNC_REPLENISH_TRADE_ACCOUNT = 500;
    const FUNC_REPLENISH_SAFE_ACCOUNT = 600;
    
    const FUNC_CREATE_LIMIT_ORDER = 700;
    const FUNC_CREATE_MARKET_ORDER = 800;
    const FUNC_CREATE_INSTANT_ORDER = 900;
    const FUNC_CANCEL_ORDER = 1000;
    
    const FUNC_CREATE_SL = 1100;
    const FUNC_CREATE_TP = 1200;
    const FUNC_CREATE_TS = 1300;
    const FUNC_CANCEL_SL = 1400;
    const FUNC_CANCEL_TP = 1500;
    const FUNC_CANCEL_TS = 1600;
    
    const FUNC_CREATE_FIX_ACCOUNT = 1700;
    const FUNC_GENERATE_NEW_FIX_PASSWORD = 1800;
    const FUNC_GET_FIX_ACCOUNT = 1900;
    const FUNC_CANCEL_FIX_ACCOUNT = 2000;
    
    
    
    const FUNC_GET_ACCOUNT_INFO = 2400;
    const FUNC_GET_ACTIVE_ORDERS = 2500;
    const FUNC_GET_ACTIVE_CONDITIONAL_ORDER = 2600;
    
    //ErrorCodes
    const ErrorAccountAlreadyExists = 1;
    const ErrorAccountNotFound = 2;
    const ErrorAccountAlreadySuspended = 3;
    const ErrorAccountAlreadyUnsuspended = 4;
    const ErrorAccountSuspended = 5;
    const ErrorCrossUserAccessDenied = 6;
    const ErrorInsufficientFunds = 7;
    const ErrorIncorrectOrderKind = 8;
    const ErrorOrderNotFound = 9;
    const ErrorInsufficientMarketVolume = 10;
    const ErrorBorrowedFundsUse = 11;
    const ErrorNegativeOrZeroSum = 12;
    const ErrorNegativeOrZeroId = 13;
    const ErrorApiKeyNotPrivileged = 14;
    const ErrorIncorrectPositionType = 15;
    const ErrorIncorrectRate = 16;
    const ErrorApiKeysLimitReached = 17;
    const ErrorApiKeyNotFound = 18;
    const ErrorSignatureDuplicate = 19;
    const ErrorNonceLessThanExpected = 20;
    const ErrorIncorrectSignature = 21;
    const ErrorNegativeOrZeroLimit = 22;
    const ErrorInvalidFunctionArguments = 23;
    const ErrorFunctionNotFound = 24;
    const ErrorInvalidJsonInput = 25;
    const ErrorNegativeOrZeroLeverage = 26;
    const ErrorIncorrectPercValue = 27;
    const ErrorUnknown = 99;
    
    public function __construct(array $input) {
        if(!isset($input['url']) || !isset($input['port'])) {
            throw new ExceptionWrongInputData();
        }

        $this->_url = $input['url'];
        $this->_port = $input['port'];

        $this->_tcpResource = stream_socket_client('tcp://'.$this->_url.':'.$this->_port, $this->_errno, $this->_errstr, self::TCP_CONNECTION_TIMEOUT);
        stream_set_timeout($this->_tcpResource, self::TCP_CONNECTION_TIMEOUT);

        if(!$this->_tcpResource) {
            throw new ExceptionConnectionRefused();
        }

        $this->_isConnetctionEstablished = true;
    }

    public function __destruct() {
        if($this->_isConnetctionEstablished) {
            $this->_isConnetctionEstablished = false;
            fclose($this->_tcpResource);
        }
    }

    private function makeRequestString($params) {
        $requestString = '{';
        $paramsCount = 0;
        $requestArray = array();
        foreach($params as $value) {
            if(is_int($value) || is_numeric($value)) {
                $requestArray[] = '"'.$paramsCount.'":'.$value;
            } else {
                $requestArray[] = '"'.$paramsCount.'":"'.$value.'"';
            }
            $paramsCount++;
        }
        $requestString .= implode(',', $requestArray).'}';
        return $requestString;
    }
    
    private function parseResponse($response, $status) {
        $responseArray = json_decode($response, true);
        if(!isset($responseArray[0]) || $responseArray[0] != $status) {
            $e = new ExceptionTcpRemoteClient();
            throw $e;
        }
        array_shift($responseArray);
        
        $data = array();
        foreach($responseArray as $value) {
            array_push($data, $value);
        }
        
        return $data;
    }
    
    public function checkResponse($status) {
        $statusArray = json_decode($status, true);
        if(!isset($statusArray[0]) || $statusArray[0] != 0) {
            $e = new ExceptionTcpRemoteClient();
            $e->errorType = (isset($responseArray[0]))?$responseArray[0]:99;
            throw $e;
        }
        
        return (isset($statusArray[1]))? $statusArray[1]:false;
    }
    
    public function sendRequest($inputArray) {
        $request = $this->makeRequestString($inputArray);
        if(!$this->_isConnetctionEstablished) {
            throw new ExceptionNotConnected();
        }

        fwrite($this->_tcpResource, $request);
        
        $result[] = fgets($this->_tcpResource, 4096);
        $result[] = fgets($this->_tcpResource, 4096);
        $status = $this->checkResponse($result[0]);
        
        return $this->parseResponse($result[1], $status);
    }

    public function getIsConnectionEstablished() {
        return $this->_isConnetctionEstablished;
    }

}