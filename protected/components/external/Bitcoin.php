<?php

class Bitcoin extends ExternalGateway {
    
    public function __construct($userId) {
        $this->currencyId = 'BTC';
        
        $account = Account::model()->findByAttributes(array(
            'type' => 'user.trading',
            'currency' => 'BTC',
            'userId' => $userId
        ));
        
        if(!$account) {
            throw new ExceptionNoAccount();
        }
        
        $this->account = $account;
    }
    
    public function transferTo($count) {
        //some transfer actions
        return true;
    }
    
    public function transferFrom($count) {
        //some transfer actions
        return true;
    }
    
}