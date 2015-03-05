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
    
    public static function callForWithdraw($address, $transactionId, $amount) {
        $request = array(
            'request' => json_encode(array(
                'action' => 'requestSend',
                'transactionOrder' => $transactionId,
                'address' => $address,
                'amount' => $amount
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
        
        return $data;
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
            $address = CoinAddress::generateNewAwating();
            
            if(!$address) {
                return false;
            }

            try {
                $coinAddress = new CoinAddress();
                $coinAddress->accountId = $accountId;
                $coinAddress->address = $address;
                $coinAddress->createdAt = TIME;
                
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
    
    public function transferTo($accountId, $transactionId = null, $amount=null, $data=null) {
        return true;
    }
    
    public function transferFrom($accountId, $transactionId, $amount, $data) {
        if(!isset($data) || !isset($data['address'])) {
            return false;
        }
        
        $response = '';
        
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
            /* withdraw */
            $accountQuery = 'SELECT * FROM "account" WHERE id=:id FOR UPDATE';
            $account = Account::model()->findBySql($accountQuery, array(':id'=>$accountId));
            if(!$account) {
                throw new Exception('Account doesn\'t exist');
            }
            
            $withdrawQuery = 'SElECT * FROM "account" WHERE "currency"=\'BTC\' AND "userId"=:userid AND "type"=\'user.withdrawWallet\' FOR UPDATE';
            $withdrawWallet = Account::model()->findBySql($withdrawQuery, array(':userid' => $account->userId));
            if(!$withdrawWallet) {
                throw new Exception('Withdrawal account doesn\'t exist');
            }

            if(bccomp($amount, $account->balance) == 1) {
                throw new Exception('Not enough money');
            }
            
            $account->balance = bcsub($account->balance, $amount); 
            $withdrawWallet->balance = bcadd($withdrawWallet->balance, $amount);
            
            $transaction = new TransactionExternal();
            $transaction->gatewayId = self::$gatewayId;
            $transaction->type = true;
            $transaction->verifyStatus = 'pending';
            $transaction->accountId = $withdrawWallet->id;
            $transaction->amount = $amount;
            $transaction->createdAt = TIME;
            $transaction->currency = 'BTC';
            
            if(!$transaction->save()) {
                throw new Exception('Transaction save error');
            }
            
            $withdrawLimit = Yii::app()->params['withdrawalLimit']['BTC'];
            if(bccomp($amount, $withdrawLimit) <= 0) {
                $result = self::callForWithdraw($data['address'], $transaction->id, $amount);
                if($result == false) {
                    throw new Exception('Wrong address given');
                }
                $response = 'done';
            } else {
                $response = 'admin';
            }
            
            $account->update();
            $withdrawWallet->update();
            
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }
        
        return $response;
    }
}