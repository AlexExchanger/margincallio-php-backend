<?php

class User extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'user';
    }

    public function rules() {
        return array(
            array('email', 'required'),
            array('id','numerical', 'integerOnly'=>true),
            array('id, password, email', 'safe'),
        );
    }
    
    private function createNewTradeAccount($userId) {
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $result = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_TRADE_ACCOUNT, $userId));
        return $result;
    }
    
    public function registerByInvite($inviteCode) {
        
    }
    
    public static function verifyNewUser($code) {
        $user = User::model()->findByAttributes(array('emailVerification'=>$code));
        if(!$user) {
            throw new ExceptionUserVerification();
        }
        
        $user->emailVerification = null;
        $user->save();
        return true;
    }
    
    
    public function registerUser($email) {
        $this->email = $email;
        $this->emailVerification = UserIdentity::trickyPasswordEncoding($email, rand(0, PHP_INT_MAX));
        
        if(!$this->validate()) {
            $errorList = $this->getErrors();
            print Response::ResponseError(json_encode($errorList));
            exit();
        }
        try {
            $this->save();
            $this->createNewTradeAccount($this->id);
        } catch(Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
               throw $e;
            }
            throw new ExceptionUserSave();
        }
        
        return MailSender::sendEmail('userActivate', $email, array('confirmCode'=>$this->emailVerification));
    }
    
}