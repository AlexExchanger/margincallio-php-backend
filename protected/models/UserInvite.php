<?php

class UserInvite extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'user_invite';
    }

    public function rules() {
        return array(
            array('id, email', 'required'),
            array('id, activated', 'safe'),
        );
    }
    
    public static function SendInviteByEmail($email) {
        
        $inviteCode = UserIdentity::trickyPasswordEncoding($email.$email, rand(0, PHP_INT_MAX));
        
        $invite = new self;
        
        $invite->id = $inviteCode;
        $invite->email = $email;
        
        if(!$invite->save()) {
            throw new ExceptionInviteSave();
        }
        
        MailSender::sendEmail('userInvite', $email, array('inviteCode'=>$inviteCode));
        return true;
    }
    
}