<?php

class BtcGateway extends ExternalGateway {
    
    protected static $gatewayId = 2;

    protected $address=null;
    
    public static function getInstance() {
        return self::model('BtcGateway')->findByPk(self::$gatewayId);
    }
    
    public static function callBtcd($function, $params=[]) {
        
        $request = array(
            'request' => json_encode(array(
                'action' => $function,
                'params' => $params
            )),
        );
        
        $request['sign'] = md5($request['request'].'salt');
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
    
    
    /*Data: accountId*/
    public static function getBillingMeta($payment, $data) {
        $accountId = $data['accountId'];
        
        $coinAddress = CoinAddress::model()->findByAttributes(array(
            'accountId' => $accountId,
            'used' => false
        ));
        
        $already = false;
        
        if($coinAddress) {
            $already = true;
        } else {
            //$address = CoinAddress::getNewAddress();
            $address = CoinAddress::generateNewAwating();
            
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
        
        return preg_replace_callback('/ADDRESS_PLACEHOLDER/', function($matches) use ($coinAddress){
            return $coinAddress->address;
        }, $payment);

    }
    
    public function transferTo($accountId, $transactionId = null, $amount=null) {
        return true;
    }
    
    public function transferFrom($accountId, $transactionId, $amount) {
        return false;
        
    }
}