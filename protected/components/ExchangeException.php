<?php

//common
class CommonException extends CException {
    protected $code = 1000;
}
class ExceptionWrongInputData extends CommonException {
    protected $code = 1001;
}
class ModelException extends CommonException {
    private $errors = array();
    protected $code = 1002;

    function __construct($message, $errors = []) {
        if (is_array($message) && $errors === []) {
            $errors = $message;
            $message = '';
        }
        $this->errors = $errors;
        Loger::errorLog($message);
        parent::__construct($message);
    }

    public function getErrors() {
        return $this->errors;
    }
}
class SystemException extends CommonException {
    private $errors = array();
    protected $code = 1003;
    
    function __construct($message, $errors = []) {
        if (is_array($message) && $errors === []) {
            $errors = $message;
            $message = '';
        }
        $this->errors = $errors;
        Loger::errorLog($message);
        parent::__construct($message);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
class NoDataException extends CommonException {
    protected $code = 1004;
}
class BitcoinDaemonException extends CommonException {
    protected $code = 1005;
    
    function __construct($message, $errors = []) {
        if (is_array($message) && $errors === []) {
            $errors = $message;
            $message = '';
        }
        
        $this->errors = $errors;
        Logger::errorLog($message);
        parent::__construct($message);
    }
}

//TcpRemoteClient
class ExceptionTcpRemoteClient extends CException {
    protected $code = 2000;
    public $errorType;
    public $message = 'TCP connection error';
}
class ExceptionConnectionRefused extends ExceptionTcpRemoteClient {
    protected $code = 2001;
    public $message = 'TCP connection can\'t be establish';
}
class ExceptionNotConnected extends ExceptionTcpRemoteClient {
    protected $code = 2002;
    public $message = 'Trying to use not established TCP connection';
}

//User Auth
class ExceptionUser extends CException {
    protected $code = 3000;
    public $message = 'Error with user';
}
class ExceptionUserSave extends ExceptionUser {
    protected $code = 3001;
    public $message = 'Save error';
}
class ExceptionUserVerification extends ExceptionUser {
    protected $code = 3002;
    public $message = 'Wrong verify code';
}
class ExceptionUserPhone extends ExceptionUser {
    protected $code = 3003;
    public $message = 'Error with user phone';
}
class ExceptionLostPassword extends ExceptionUser {
    protected $code = 3004;
    public $message = 'No user with following data';
}

//Admin
class ExceptionAdmin extends CException {
    protected $code = 4000;
    public $message = 'Unknow error';
}
class ExceptionInviteSave extends ExceptionAdmin {
    protected $code = 4001;
    public $message = 'Can\'t generate invite code';
}

//Notification
class ExceptionNotification extends CException {
    protected $code = 5000;
    public $message = 'Error with notification';
}
class ExceptionNotificationNoView extends ExceptionNotification {
    protected $code = 5001;
    public $message = 'There is no view file with given name';
}


//Account
class ExceptionAccount extends CException {
    protected $code = 6000;
    public $message = 'Error with account';
}
class ExceptionWrongCurrency extends ExceptionAccount {
    protected $code = 6001;
    public $message = 'Currency does\'t supported';
}
class ExceptionNoMoney extends ExceptionAccount {
    protected $code = 6002;
    public $message = 'This wallet has no money';
}
class ExceptionWrongTicketId extends ExceptionAccount {
    protected $code = 6003;
    public $message = 'Wrong ticket ID';    
}

//External Gateway
class ExceptionGateway extends CException {
    protected $code = 7000;
    public $message = 'Error with gateway';
}
class ExceptionNoAccount extends ExceptionGateway {
    protected $code = 7001;
    public $message = 'There is no account for this user and this currency';
}

//Trade & Orders
class ExceptionTrade extends CException {
    protected $code = 8000;
    public $message = 'Error with trade operation';
}
class ExceptionUnknowOrderType extends ExceptionTrade {
    protected $code = 8001;
    public $message = 'Unknow order type';
}
class ExceptionOrderNonExist extends ExceptionTrade {
    protected $code = 8002;
    public $message = 'This order doesn\'t exist';
}