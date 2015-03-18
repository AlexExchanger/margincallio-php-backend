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
    //const FUNC_CREATE_INSTANT_ORDER = 900;
    const FUNC_CANCEL_ORDER = 900;
    
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
    
    const FUNC_GENERATE_API_KEY = 2100;
    const FUNC_GET_API_KEY = 2200;
    const FUNC_CANCEL_API_KEY = 2300;
    
    
    const FUNC_GET_ACCOUNT_INFO = 2500;
    //const FUNC_GET_ACTIVE_ORDERS = 2500;
    const FUNC_GET_ACCOUNT_BALANCE = 2400;
    //const FUNC_GET_ACTIVE_CONDITIONAL_ORDER = 2600;
//    const FUNC_GET_ACTIVE_SL_ORDER = 2600;
//    const FUNC_GET_ACTIVE_TP_ORDER = 2700;
//    const FUNC_GET_ACTIVE_TS_ORDER = 2800;
    const FUNC_GET_ACCOUNT_FEE = 2600;
    const FUNC_GET_ACTIVE_ORDERS = 2700;
    const FUNC_GET_ORDER_INFO = 2800;
    
    const FUNC_GET_ACTIVE_CONDITIONAL_ORDER = 3000;
    
    const FUNC_SET_ACCOUNT_FEE = 1000;
    const FUNC_GET_TICKER = 7000;
    const FUNC_GET_DEPTH = 7100;
    
    const FUNC_GET_MARGIN_PARAM = 3400;
    const FUNC_SET_MAX_LEVERAGE = 3500;
    
    const FUNC_SET_MC_LEVEL = 3600;
    const FUNC_SET_FL_LEVEL = 3700;
    
    const FUNC_CREATE_CURRENCY_PAIR = 5000;
    const FUNC_GET_CURRENCY_PAIRS = 5100;
    const FUNC_GET_DERIVED_CURRENCIES = 5200;
    const FUNC_DELETE_CURRENCY_PAIR = 5300;
    
    const FUNC_MAKE_SNAPSHOT = 9000;
    
    const FUNC_OPEN_MARKET = 8900;
    const FUNC_CLOSE_MARKET = 8800;
    const FUNC_RESTART_FIX = 79500;
    const FUNC_BACKUP_MASTER_SNAPSHOT = 80000;
    const FUNC_RESTORE_MASTER_SNAPSHOT = 80100;
    const FUNC_RESTORE_SLAVE_SNAPSHOT = 80200;
    
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
    const ErrorIncorrectStopLossRate = 15;
    const ErrorIncorrectTakeProfitRate = 16;
    const ErrorIncorrectTrailingStopOffset = 17;
    const ErrorApiKeysLimitReached = 18;
    const ErrorApiKeyNotFound = 19;
    const ErrorSignatureDuplicate = 20;
    const ErrorNonceLessThanExpected = 21;
    const ErrorIncorrectSignature = 22;
    const ErrorNegativeOrZeroLimit = 23;
    const ErrorInvalidFunctionArguments = 24;
    const ErrorFunctionNotFound = 25;
    const ErrorInvalidJsonInput = 26;
    const ErrorNegativeOrZeroLeverage = 27;
    const ErrorIncorrectPercValue = 28;
    const ErrorFixAccountsLimitReached = 29;
    const ErrorFixRestartFailed = 30;
    const ErrorFixAccountAlreadyExists = 31;
    const ErrorFixAccountNotFound = 32;
    const ErrorFixSymbolNotFound = 33;
    const ErrorFixFieldsNotSet = 34;
    const ErrorFixInvalidClOrdID = 35;
    const ErrorFixUnknownOrderType = 36;
    const ErrorFixInvalidOrderId = 37;
    const ErrorSnapshotBackupFailed = 38;
    const ErrorSnapshotRestoreFailed = 39;
    const ErrorMarketClosed = 40;
    const ErrorMarketAlreadyClosed = 41;
    const ErrorMarketAlreadyOpened = 42;
    const ErrorMarketOpened = 43;
    const ErrorBackupRestoreInProc = 44;
    const ErrorIPDuplicate = 45;
    const ErrorInvalidCurrency = 46;
    const ErrorInvalidCurrencyPair = 47; 
    const ErrorCurrencyNotFound = 48;
    const ErrorCurrencyPairNotFound = 49; 
    const ErrorCurrencyPairAlreadyExists = 50;
    const ErrorStopLossUnavailable = 51;
    const ErrorTakeProfitUnavailable = 52;
    const ErrorTrailingStopUnavailable = 53;
    
    const ErrorUnknown = 99;
    
    public function __construct(array $input=null) {
        if(is_null($input)) {
            $input = Yii::app()->params->coreUsdBtc;
        }
        
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
            $e->errorType = $responseArray[0];
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
            throw new ExceptionTcpRemoteClient((isset($responseArray[0]))?$responseArray[0]:99);
        }
        
        return (isset($statusArray[1]))? $statusArray[1]:false;
    }
    
    public function sendRequest($inputArray) {
        $request = $this->makeRequestString($inputArray);
        if(!$this->_isConnetctionEstablished) {
            throw new ExceptionNotConnected();
        }

        fwrite($this->_tcpResource, $request);
        
        $result = array();
        $result[] = fgets($this->_tcpResource, 4096);
        $status = $this->checkResponse($result[0]);
        $result[]= fgets($this->_tcpResource, 4096);
        
        return $this->parseResponse($result[1], $status);
    }

    public function getIsConnectionEstablished() {
        return $this->_isConnetctionEstablished;
    }

}