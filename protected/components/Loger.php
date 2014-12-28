<?php

class Loger extends CComponent {
    
    private static function getMessageHeader() {
        return '['.date('Y-m-d H:i:s').']: ';
    }
    
    public static function errorLog($message, $userId = null, $action = null) {
        $fileName = date('d-m-Y').'.log';
        $dir = implode(DIRECTORY_SEPARATOR, array(Yii::getPathOfAlias('webroot'), 'logs', 'error', date('Y-m')));
        if(!file_exists($dir)) {
            mkdir($dir, 0777);
        }

        $actionMessage = ($action != null)? 'Action: '.$action.'. ':'';
        $userMessage = ($userId != null)? 'User: '.$userId.'. ':'';
        
        $msg = is_array($message)? implode("\r\n", $message):$message;
        $fullMessage = self::getMessageHeader().$userMessage.$actionMessage.$msg."\r\n";
        
        file_put_contents($dir.DIRECTORY_SEPARATOR.$fileName, $fullMessage, FILE_APPEND | LOCK_EX);        
    }
    
    public static function logSecureAction($userId, $action, $message) {
        try {
            UserLog::addAction($userId, $action, $message);
        } catch(Exception $e) {
            return false;
        }
    }
    
    public static function logUser($userId, $message, $secure = null) {
        $fileName = date('d-m-Y').'.log';
        
        $dir = implode(DIRECTORY_SEPARATOR, array(Yii::getPathOfAlias('webroot'), 'logs', 'user', $userId));
        if(!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        
        $dir = implode(DIRECTORY_SEPARATOR, array(Yii::getPathOfAlias('webroot'), 'logs', 'user', $userId, date('Y-m')));
        if(!file_exists($dir)) {
            mkdir($dir, 0777);
        }

        $fullMessage = self::getMessageHeader().$message."\r\n";
        
        file_put_contents($dir.DIRECTORY_SEPARATOR.$fileName, $fullMessage, FILE_APPEND | LOCK_EX);
        if($secure != null) {
            self::logSecureAction($userId, $secure, $message);
        }
    }
    
    public static function logAdmin($userId, $message, $secure = null) {
        $fileName = date('d-m-Y').'.log';
        
        $dir = implode(DIRECTORY_SEPARATOR, array(Yii::getPathOfAlias('webroot'), 'logs', 'admin', $userId));
        if(!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        
        $dir = implode(DIRECTORY_SEPARATOR, array(Yii::getPathOfAlias('webroot'), 'logs', 'admin', $userId, date('Y-m')));
        if(!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        
        $fullMessage = self::getMessageHeader().$message."\r\n";
        
        file_put_contents($dir.DIRECTORY_SEPARATOR.$fileName, $fullMessage, FILE_APPEND | LOCK_EX);
        if($secure != null) {
            self::logSecureAction($userId, $secure, $message);
        }
    }
    
}