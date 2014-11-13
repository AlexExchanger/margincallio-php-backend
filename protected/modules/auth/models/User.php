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
            array('id, password, email, status', 'safe'),
        );
    }
    
    private function createNewTradeAccount($userId) {
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $result = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_TRADE_ACCOUNT, $userId));
        return $result;
    }
    
    public function registerByInvite($inviteCode) {
        
    }
    
    public function registerUser($email) {
        $this->email = $email;
        $this->emailVerification = UserIdentity::trickyPasswordEncoding($email, rand(0, PHP_INT_MAX));
        
        if(!$this->validate()) {
            $errorList = $this->getErrors();
            print Response::ResponseError(json_encode($errorList));
            exit();
        }
        
        if(!$this->save()){
            throw new ExceptionUserSave();
        }
        
        MailSender::sendEmail('userActivate', $email, array('confirmCode'=>$this->emailVerification));
    }
    
}