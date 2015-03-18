<?php

class MailSender extends CComponent {
    
    public static function sendEmail($type, $to, $data=array()) {
        require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.'/extensions/PHPMailer/PHPMailerAutoload.php';
        
        $viewPath = 'views'.DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.$type.'.php';
        $fullPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$viewPath;
        
        if(!file_exists($fullPath)) {
            throw new ExceptionNotificationNoView();
        }
        
        $fullMessagePath = Yii::app()->controller->renderFile($fullPath, $data, true);

        $headers = 'Content-type: text/html; charset=utf-8' . "\r\n";
        
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet    = 'UTF-8';
        $mail->Host       = 'mail.spacebtc.com'; 
        $mail->SMTPAuth   = true;
        $mail->Port       = 587;
        $mail->Username   = 'noreply@spacebtc.com';
        $mail->Password   = '43sahTMT4b';    
        $mail->SMTPSecure = 'tls';
        
        $mail->SMTPDebug  = 0;
        
        $mail->From = 'noreply@spacebtc.com';
        $mail->isHTML(true);
        $mail->addAddress($to);
        $mail->Subject = isset($data['subject'])?$data['subject']:'SpaceBTC';
        $mail->Body = $fullMessagePath;
        
        $status = $mail->send();
        if(!$status) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
            die();
        }
       
        return $status;
    }
    
    public static function getMailChimpList($type) {
        $list = array(
            'early' => 'e96203f2e8',
        );
        
        if(isset($list[$type])) {
            return $list[$type];
        }
        
        return false;
    }
    
    public static function sendToMailChimp($type, $data = array()) {
        include Yii::getPathOfAlias('webroot').'/protected/extensions/MailChimp.php';
        
        $api_key = 'f110bad0d9d9b569ad3b11840bb3109e-us10';
        
        $mailChimp = new \Drewm\MailChimp($api_key);
        return $mailChimp->call($type, $data);
    }
    
}
