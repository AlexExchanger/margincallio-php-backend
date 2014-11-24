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
            array('email', 'email'),
            array('id','numerical', 'integerOnly'=>true),
            array('id, password, email', 'safe'),
        );
    }
    
    private function createTradeAccount($userId) {
        //currency by default
        $defaultCurrency = array('USD', 'BTC');
        $defaultTypes = array('user.safeWallet', 'user.trading', 'user.withdrawWallet');
        
        foreach($defaultTypes as $type) {
            foreach($defaultCurrency as $value) {
                Account::create(array(
                    'userId' => $userId,
                    'currency' => $value,
                    'status' => 'opened',
                    'type' => $type
                ));
            }
        }
        
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $result = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_TRADE_ACCOUNT, $userId));
        
        return $result;
    }
    
    public function registerByInvite($inviteCode) {
        $userInvite = UserInvite::model()->findByPk($inviteCode);
        
        if(!$userInvite || $userInvite->activated == true) {
            throw new ExceptionUserVerification();
        }
        
        $userInvite->activated = true;
        $userInvite->save();
        
        $this->email = $userInvite->email;
        $this->emailVerification = null;
        
        if(!$this->validate()) {
            $errorList = $this->getErrors();
            print Response::ResponseError(json_encode($errorList));
            exit();
        }
        try {
            $this->save();
            $this->createTradeAccount($this->id);
        } catch(Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
               throw $e;
            }
            throw new ExceptionUserSave();
        }
        
        return true;
    }
    
    public static function lostPassword($email, $phone) {
        
        $user = User::model()->findByAttributes(array('email'=>$email));
        if(!$user) {
            throw new ExceptionLostPassword();
        }
        
        $phoneId = UserIdentity::trickyPasswordEncoding($user->email, $user->id);
        $userPhone = UserPhone::model()->findByPk($phoneId);
        
        if(!$userPhone) {
            throw new ExceptionLostPassword();
        }
        
        $newVerifyCode = UserIdentity::trickyPasswordEncoding($email, rand(0, PHP_INT_MAX));
        $user->emailVerification = $newVerifyCode;
        $user->password = null;
        
        MailSender::sendEmail('lostPassword', $email, array('confirmCode'=>$newVerifyCode));
        
        if(!$user->save()) {
            throw new ExceptionUserSave();
        }
        
        return true;
    }
    
    public static function verifyNewUser($code) {
        
        //TODO: Action for frond-end.
        /*$user = User::model()->findByAttributes(array('emailVerification'=>$code));
        if(!$user) {
            throw new ExceptionUserVerification();
        }
        
        $user->emailVerification = null;
        $user->save();*/
        return true;
    }
    
    public static function continueVerifying($cid, $password, $phone) {
        $user = self::model()->findByAttributes(array('emailVerification'=>$cid));
        
        if(!$user) {
            throw new ExceptionUserVerification();
        }
        
        $user->password = UserIdentity::trickyPasswordEncoding($user->email, $password);
        $user->emailVerification = null;
        
        if(!$user->save()) {
            throw new ExceptionUserSave();
        }
        
        $userPhone = new UserPhone();
        $userPhone->id = UserIdentity::trickyPasswordEncoding($user->email, $user->id);
        $userPhone->phone = $phone;
        
        $userPhone->addPhone();
        
        return true;
    }
    
    public static function changeLostPassword($cid, $password) {
        
        $user = User::model()->findByAttributes(array('emailVerification'=>$cid));
        if(!$user) {
            throw new ExceptionUserVerification();
        }
        
        $user->password = UserIdentity::trickyPasswordEncoding($user->email, $password);
        $user->emailVerification = null;
        
        if(!$user->save()) {
            throw new ExceptionUserSave();
        }
        
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
            $this->createTradeAccount($this->id);
        } catch(Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
               throw $e;
            }
            throw new ExceptionUserSave();
        }
        
        return MailSender::sendEmail('userActivate', $email, array('confirmCode'=>$this->emailVerification));
    }
    
    public static function get($userId) {
        return self::model()->findByPk($userId);
    }
    
}