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

        //$headers = 'Content-type: text/html; charset=utf-8' . "\r\n";
        
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet    = 'UTF-8';
        $mail->Host       = 'mail.spacebtc.com'; 
        $mail->SMTPAuth   = true;
        $mail->Port       = 25;
        $mail->Username   = 'noreply@spacebtc.com';
        $mail->Password   = '43sahTMT4b';    
        
        $mail->SMTPDebug  = 3;
        
        $mail->From = 'noreply@spacebtc.com';
        $mail->isHTML(true);
        $mail->addAddress($to);
        $mail->Subject = isset($data['subject'])?$data['subject']:'Rocket BTC';
        $mail->Body = $fullMessagePath;
        
        $status = $mail->send();
        if(!$status) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
            die();
        }
        
        //$status = mail($to, isset($data['subject'])?$data['subject']:'Rocket BTC', $fullMessagePath, $headers);

        return $status;
    }
    
    
}
