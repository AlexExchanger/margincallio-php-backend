<?php

class BtcGateway extends ExternalGateway {
    
    protected static $gatewayId = 2;

    protected $address=null;
    
    public static function getInstance() {
        return self::model('BtcGateway')->findByPk(self::$gatewayId);
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
    
    public function setAddress($address) {
        $this->address = $address;
    }
    
    public function transferTo($accountId, $amount) {
        
        $prevAddresses = CoinAddress::model()->findByAttributes(array(
            'accountId' => $accountId,
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
        $coinAddress->accountId = $accountId;
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
    
    public function transferFrom($accountId, $amount) {
        
        if(is_null($this->address)) {
            return false;
        }
        
    }
}