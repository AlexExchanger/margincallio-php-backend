<?php

class MailSender extends CComponent {
    
    public static function sendEmail($type, $to, $data=array()) {
        $viewPath = 'views'.DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.$type.'.php';
        $fullPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$viewPath;
        
        if(!file_exists($fullPath)) {
            throw new ExceptionNotificationNoView();
        }
        
        $fullMessagePath = Yii::app()->controller->renderFile($fullPath, $data, true);

        $headers = 'Content-type: text/html; charset=utf-8' . "\r\n";
        
        $status = mail($to, isset($data['subject'])?$data['subject']:'Rocket BTC', $fullMessagePath, $headers);

        return $status;
    }
    
    
}
