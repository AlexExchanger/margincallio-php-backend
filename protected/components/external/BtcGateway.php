<?php

class BtcGateway extends ExternalGateway {
    
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
    
    public function transferTo($address=null, $count=null) {
        
        $response = array();
        $userId = $this->account->userId;
        
        $prevAddresses = CoinAddress::model()->findByAttributes(array(
            'accountId' => $userId,
            'used' => false
        ));
        
        if($prevAddresses) {
            return array(
                'already' => true,
                'object' => $prevAddresses
            );
        }
        
        $address = CoinAddress::getNewAddress();
        
        if(!$address) {
            return false;
        }
        
        $coinAddress = new CoinAddress();
        $coinAddress->accountId = $userId;
        $coinAddress->address = $address;
        $coinAddress->createdAt = TIME;
        if(!$coinAddress->save()) {
            return false;
        }
        
        return array(
                'already' => false,
                'object' => $coinAddress
            );
    }
    
    public function transferFrom($address, $count) {
        //some transfer actions
        return true;
    }
    
}