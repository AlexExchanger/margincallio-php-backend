<?php

//common
class ExceptionWrongInputData extends CException {}

//TcpRemoteClient
class ExceptionTcpRemoteClient extends CException {
    public $errorType;
    public $message = 'TCP connection error';
}
class ExceptionConnectionRefused extends ExceptionTcpRemoteClient {
    public $message = 'TCP connection can\'t be establish';
}
class ExceptionNotConnected extends ExceptionTcpRemoteClient {
    public $message = 'Trying to use not established TCP connection';
}

//User Auth
class ExceptionUser extends Exception {
    public $message = 'Error with user';
}
class ExceptionUserSave extends ExceptionUser {
    public $message = 'Save error';
}
class ExceptionUserVerification extends ExceptionUser {
    public $message = 'Wrong verify code';
}
class ExceptionUserPhone extends ExceptionUser {
    public $message = 'Error with user phone';
}
class ExceptionLostPassword extends ExceptionUser {
    public $message = 'No user with following data';
}

//Admin
class ExceptionAdmin extends Exception {
    public $message = 'Unknow error';
}
class ExceptionInviteSave extends ExceptionAdmin {
    public $message = 'Can\'t generate invite code';
}


//Notification
class ExceptionNotification extends Exception {
    public $message = 'Error with notification';
}
class ExceptionNotificationNoView extends ExceptionNotification {
    public $message = 'There is no view file with given name';
}


//Account
class ExceptionAccount extends Exception {
    public $message = 'Error with account';
}

class ExceptionWrongCurrency extends ExceptionAccount {
    public $message = 'Currency does\'t supported';
}

class ExceptionNoMoney extends ExceptionAccount {
    public $message = 'This wallet has no money';
}

//External Gateway
class ExceptionGateway extends Exception {
    public $message = 'Error with gateway';
}

class ExceptionNoAccount extends ExceptionGateway {
    public $message = 'There is no account for this user and this currency';
}

//Trade & Orders
class ExceptionTrade extends Exception {
    public $message = 'Error with trade operation';
}

class ExceptionUnknowOrderType extends ExceptionTrade {
    public $message = 'Unknow order type';
}

class ExceptionOrderNonExist extends ExceptionTrade {
    public $message = 'This order doesn\'t exist';
}