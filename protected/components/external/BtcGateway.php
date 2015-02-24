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
    
    public function transferTo($accountId, $transactionId = null, $amount=null) {
        
        $coinAddress = CoinAddress::model()->findByAttributes(array(
            'accountId' => $accountId,
            'used' => false
        ));
        
        $already = false;
        
        if($coinAddress) {
            $already = true;
        } else {
            //$address = CoinAddress::getNewAddress();
            $address = CoinAddress::generateNewAwating($amount);
            
            if(!$address) {
                return false;
            }

            try {
            
                $externalTransaction = new TransactionExternal();
                $externalTransaction->createdAt = TIME;
                $externalTransaction->currency = 'BTC';
                $externalTransaction->gatewayId = BtcGateway::$gatewayId;
                $externalTransaction->type = false;
                $externalTransaction->verifyStatus = 'pending';
                $externalTransaction->accountId = $accountId;

                if(!$externalTransaction->save()) {
                    throw new SystemException('Unable to save transaction');
                }

                $coinAddress = new CoinAddress();
                $coinAddress->accountId = $accountId;
                $coinAddress->address = $address;
                $coinAddress->createdAt = TIME;
                $coinAddress->transactionId = $externalTransaction->id;

                if(!$coinAddress->save()) {
                    throw new SystemException('Unable to save coin address');
                }
            } catch (Exception $e) {
                return false;
            }
        }
        
        return array(
            'already' => $already,
            'object' => $coinAddress
        );
    }
    
    public function transferFrom($accountId, $transactionId, $amount) {
        return false;
        
    }
}