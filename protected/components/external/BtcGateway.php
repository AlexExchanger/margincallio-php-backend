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
    
    public static function callBtcd($function, $params=[]) {
        
        $request = array(
            'request[action]' => $function,
            'time' => TIME,
            'request[params]' => json_encode($params)
        );
        
        $request['sign'] = md5($request['request[action]'].TIME.'salt');
        $bitcoinService = curl_init();
        curl_setopt_array($bitcoinService, array(
            CURLOPT_URL => Yii::app()->params->bitcoinService['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request,
        ));
        
        $response = curl_exec($bitcoinService);
        curl_close($bitcoinService);

        if(!$response) {
            return false;
        }
        
        $data = json_decode($response, true);
        
        if(!$data['success']) {
            return false;
        }
        
        return $data['data'];
    }
    
    
}