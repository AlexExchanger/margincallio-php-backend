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
    const FUNC_CREATE_TRADE_ACCOUNT = 1;
    const FUNC_LOCK_TRADE_ACCOUNT = 2;
    const FUNC_UNLOCK_TRADE_ACCOUNT = 3;
    const FUNC_REMOVE_TRADE_ACCOUNT = 4;
    const FUNC_REPLENISH_TRADE_ACCOUNT = 5;
    
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
        foreach($params as $value) {
            $requestString .= '"'.$paramsCount.'":"'.$value.'"';
            $paramsCount++;
        }
        $requestString .= '}';
        
        return $requestString;
    }
    
    private function parseResponse($response) {
        $responseArray = json_decode($response, true);
        if(!isset($responseArray[0]) || $responseArray[0] != 0) {
            throw new ExceptionTcpRemoteClient();
        }
        array_shift($responseArray);
        
        $data = array();
        foreach($responseArray as $value) {
            array_push($data, $value);
        }
        
        return $data;
    }
    
    public function sendRequest($inputArray) {
        $request = $this->makeRequestString($inputArray);
        if(!$this->_isConnetctionEstablished) {
            throw new ExceptionNotConnected();
        }

        $result = '';
        fwrite($this->_tcpResource, $request);
        $result .= fread($this->_tcpResource, 1024);

        return $this->parseResponse($result);
    }

    public function getIsConnectionEstablished() {
        return $this->_isConnetctionEstablished;
    }

}