<?php

//common
class ExceptionWrongInputData extends CException {}

//TcpRemoteClient
class ExceptionTcpRemoteClient extends CException {
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



//Notification
class ExceptionNotification extends Exception {
    public $message = 'Error with notification';
}
class ExceptionNotificationNoView extends ExceptionNotification {
    public $message = 'There is no view file with given name';
}
