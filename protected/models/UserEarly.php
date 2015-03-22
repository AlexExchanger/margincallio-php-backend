<?php

class UserEarly extends CActiveRecord { 
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'user_early';
    }
    
    public function rules() {
        return array(
            array('email', 'required'),
            array('email', 'email'),
            array('email', 'unique', 'message'=>'You already register for early access'),
        );
    }
    
    public static function add($email) {
        
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {

            $user = new UserEarly();
            $user->email = mb_strtolower($email);
            $user->ip = Yii::app()->request->getUserHostAddress();
            
            if(!$user->validate()) {
                throw new Exception('Wrong email');
            }
            
            if(!$user->save()) {
                throw new Exception('Can\'t save email');
            }
            
            $mailchimp = array(
                'id'                => MailSender::getMailChimpList('early'),
                'email'             => array('email'=>$email),
                'double_optin'      => false,
                'update_existing'   => true,
                'replace_interests' => false,
                'send_welcome'      => false,
            );
            
            $mcresult = MailSender::sendToMailChimp('lists/subscribe', $mailchimp);
            
            if(isset($mcresult) && isset($mcresult['status']) && $mcresult['status'] == 'error') {
                throw new Exception('Error to sign up this email');
            }
            
            MailSender::sendEmail('earlyAccess', $email);
            
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }
    }
    
}