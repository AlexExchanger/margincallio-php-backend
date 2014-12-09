<?php

class User extends CActiveRecord {

    public static $typeOptions = array(
        'trader',
        'market.maker',
        'partner',
        'support',
        'admin',
        'senior.accountant',
        'internal.accountant',
        'gateway.accountant',
        'gateway.treasurer',
        'reserve.treasurer',
        'verifier',
        'general.verifier',
        'admin.stat.observer',
    );
    public static $verifiedStatusOptions = array(
        'waitingForDocuments',
        'waitingForModeration', 
        'accepted',
        'rejected',
    );
    
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
            array('type', 'in', 'allowEmpty' => false, 'range' => self::$typeOptions, 'strict' => true),
            array('verifiedStatus', 'in', 'allowEmpty' => false, 'range' => self::$verifiedStatusOptions, 'strict' => true),
            array('id','numerical', 'integerOnly'=>true),
            array('id, password, email', 'safe'),
        );
    }
    
    private function createAccountWallet($userId) {
        //currency by default
        $defaultCurrency = array('USD', 'BTC');
        $defaultTypes = array('user.safeWallet', 'user.withdrawWallet');
        
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
        
        return true;
    }
    
    private static function createTradeAccountWallet($userId) {
        //currency by default
        $defaultCurrency = array('USD', 'BTC');
        $type = 'user.trading';
        
        foreach($defaultCurrency as $value) {
            Account::create(array(
                'userId' => $userId,
                'currency' => $value,
                'status' => 'opened',
                'type' => 'user.trading'
            ));
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
            $this->createAccountWallet($this->id);
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
        $user = User::model()->findByAttributes(array('emailVerification'=>$code));
        if(!$user) {
            throw new ExceptionUserVerification();
        }
        
        $user->emailVerification = null;
        $user->save();
        
        return true;
    }
    
    public static function continueVerifying($cid, $password, $phone) {
        $user = self::model()->findByAttributes(array('emailVerification'=>$cid));
        
        if(!$user) {
            throw new ExceptionUserVerification();
        }
        
        $user->password = UserIdentity::trickyPasswordEncoding($user->email, $password);
        $user->emailVerification = null;
        $user->verifiedStatus = 'waitingForDocuments';
        $user->type = 'trader';
        
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
    
    public static function getCurrent() {
        if(Yii::app()->user->isGuest) {
            return false;
        }
        
        return self::get(Yii::app()->user->id);
    }
    
    public static function LockUser($userId) {
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $connector->sendRequest(array(TcpRemoteClient::FUNC_LOCK_TRADE_ACCOUNT, $userId));
        
        $user = User::model()->findByPk($userId);
        $user->blocked = true;
        $result = $user->save();
        return $result;
    }
    
    public static function UnlockUser($userId) {
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $connector->sendRequest(array(TcpRemoteClient::FUNC_UNLOCK_TRADE_ACCOUNT, $userId));
        
        $user = User::model()->findByPk($userId);
        $user->blocked = false;
        $result = $user->save();
        return $result;
    }
    
    public static function RemoveUser($userId) {
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $connector->sendRequest(array(TcpRemoteClient::FUNC_REMOVE_TRADE_ACCOUNT, $userId));
        
        $user = User::model()->findByPk($userId);
        $accounts = Account::model()->findAllByAttributes(array('userId'=>$userId));
        foreach($accounts as $value) {
            $value->delete();
        }
        
        $userPhone = UserPhone::model()->findByPk(UserIdentity::trickyPasswordEncoding($user->email, $user->id));
        if($userPhone) {
            $userPhone->delete();
        }
        
        $result = $user->delete();
        
        return $result;
    }
    
    public static function getLoginData($user) {
        $supportedPair = Yii::app()->params->supportedPair;
        
        return array(
            'id' => $user->id,
            'supportedPair' => $supportedPair,
            'defaultPair' => $supportedPair[0],
            '2fa' => $user->twoFA,
            'verified' => ($user->verifiedStatus == 'accepted')? true:false
        );
    }
    
    public static function getGeneralStatistic() {
        $user = self::getCurrent();
        
        $stats = Transaction::getStats(array(
            'accountId' => $user->id,
            'dateFrom' => $user->lastLoginAt,
        ));
        
        return $stats;
    }
    
    public static function getForModeration($pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = new CDbCriteria();
        if ($limit) {
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        $criteria->addCondition('"verifiedStatus"=\'waitingForModeration\'', 'AND');
        $users = self::model()->findAll($criteria);
        
        $data = array();
        foreach($users as $value) {
            $data[] = array(
                'id' => $value->id,
                'email' => $value->email,
                'type' => $value->type,
                'createdAt' => $value->createdAt,
                'lastLoginAt' => $value->lastLoginAt,
            );
        }
        
        return $data;
    }

    public static function verify($userId) {
        $adminId = Yii::app()->user->id;
        
        $user = self::get($userId);
        $user->verifiedBy = $adminId;
        $user->verifiedAt = TIME;
        $user->verifiedStatus = 'accepted';
        
        if($user->save()) {
            return self::createTradeAccountWallet($userId);
        }
        
        return false;
    }
    
    public static function refuse($userId, $reason) {
        $adminId = Yii::app()->user->id;
        
        $user = self::get($userId);
        $user->verifiedBy = $adminId;
        $user->verifiedAt = TIME;
        $user->verifiedStatus = 'rejected';
        $user->verifiedReason = $reason;
        
        return $user->save();
    }
    
    
}