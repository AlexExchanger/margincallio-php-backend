<?php

class BitcoinController extends CController
{
    
    public function actionTransaction() {
    
        $salt = 'salt';
      
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
            if (!Service::checkRequest($salt)) {
                throw new BitcoinDaemonException('Wrong request sign. Request: '.json_encode($_POST));
            }

            $request = $_POST['request'];
            
            $txid = ArrayHelper::getFromArray($request, 'txid');
            $address = ArrayHelper::getFromArray($request, 'address');
            
            //select for update
            $coinAddressQuery = 'SELECT * FROM "coin_address" WHERE "address"=:address FOR UPDATE';
            $coinAddress = CoinAddress::model()->findBySql($coinAddressQuery, array(
               ':address' => $address,
            ));
            
            if (!$coinAddress) {
                throw new BitcoinDaemonException('CoinAddress not found. Request: '.json_encode($_POST));
            }
            
            if(!is_null($coinAddress->transactionId)) {
                if(isset($coinAddress->lastTx) && $coinAddress->lastTx != $txid) {
                    //daemon notify
                }
                
                throw new SystemException('Already done');
            }
            
            $externalTransaction = new TransactionExternal();
            $externalTransaction->createdAt = TIME;
            $externalTransaction->currency = 'BTC';
            $externalTransaction->gatewayId = 2;
            $externalTransaction->type = false;
            $externalTransaction->verifyStatus = 'pending';
            $externalTransaction->accountId = $coinAddress->accountId;
            
            
            if(!$externalTransaction->save()) {
                throw new SystemException('Unable to save transaction');
            }
            
            $coinAddress->transactionId = $externalTransaction->id;
            $coinAddress->update();
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionReceived()
    {
        $salt = 'salt';
        try {
            if (!Service::checkRequest($salt)) {
                throw new BitcoinDaemonException('Wrong request sign. Request: '.json_encode($_POST));
            }

            $request = $_POST['request'];
            
            $address = ArrayHelper::getFromArray($request, 'address');
            $amount = ArrayHelper::getFromArray($request, 'amount');
            $txid = ArrayHelper::getFromArray($request, 'txid');

            $coinAddress = CoinAddress::getByAddress($address);
            if (!$coinAddress) {
                throw new BitcoinDaemonException('CoinAddress not found. Request: '.json_encode($_POST));
            }
            
            if(isset($coinAddress->lastTx) && $coinAddress->lastTx == $txid) {
                throw new BitcoinDaemonException('Transaction already done!. Request: '.json_encode($_POST));
            }
            
        } catch(Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        
        //transaction update
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
            $transactionQuery = 'SELECT * FROM "transaction_external" WHERE "id"='.$coinAddress->transactionId." FOR UPDATE";
            $transaction = TransactionExternal::model()->findBySql($transactionQuery);
            if(!$transaction) {
                throw new SystemException('Transaction with id '.$coinAddress->transactionId.' doesn\'t exist!');
            }

            if($transaction->verifyStatus != 'pending') {
                
                $newTransaction = new TransactionExternal();
                $newTransaction->createdAt = TIME;
                $newTransaction->currency = 'BTC';
                $newTransaction->gatewayId = 2;
                $newTransaction->type = false;
                $newTransaction->verifyStatus = 'done';
                $newTransaction->accountId = $coinAddress->accountId;
                $newTransaction->amount = $amount;
                $newTransaction->details = json_encode(array(
                    'txid' => $txid,
                    'address' => $address
                ));
                
                if(!$newTransaction->save()) {
                    throw new SystemException('Error with additional transaction created!');
                }
                
            } else {
                $transaction->amount = $amount;
                $transaction->verifyStatus = 'done';
                $transaction->details = json_encode(array(
                    'txid' => $txid,
                    'address' => $address
                ));
                
            }
            
            $transaction->update();   
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            Response::ResponseError();
        }
        
        //account update
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {   
            $query = 'SELECT * FROM "account" WHERE "id"='.$coinAddress->accountId." FOR UPDATE";
            $account = Account::model()->findBySql($query);
            if (!$account || $account->currency != 'BTC') {
                throw new SystemException('Account is not correct', array('request' => $_POST));
            }
            $account->balance = bcadd($account->balance, $amount);
            $account->update();
            
            /*push for daemon*/
            
            $coinAddress->lastTx = $txid;
            $coinAddress->used = true;
            $coinAddress->update();
            
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }

    public function actionSent()
    {
        $salt = 'salt';
        
        try {
            if (!Service::checkRequest($salt)) {
                throw new BitcoinDaemonException('Wrong request sign. Request: '.json_encode($_POST));
            }

            $request = $_POST['request'];
        
            $transactionOrders = ArrayHelper::getFromArray($request, 'transactionOrders');
            $txid = ArrayHelper::getFromArray($request, 'txid');
            
            
        } catch(Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
            $transactionId = '';
            if(is_array($transactionOrders)) {
                $transactionId = $transactionOrders[0];
            } else {
                $transactionId = $transactionOrders;
            }
            
            
            $transactionQuery = 'SELECT * FROM "transaction_external" WHERE id=:id FOR UPDATE';
            $transaction = TransactionExternal::model()->findBySql($transactionQuery, array(':id'=>$transactionId));
            if(!$transaction || $transaction->verifyStatus == 'done') {
                throw new SystemException('Transactin already done');
            }
            
            $withdrawQuery = 'SELECT * FROM "account" WHERE id=:withdrawid FOR UPDATE';
            $withdraw = Account::model()->findBySql($withdrawQuery, array(':withdrawid'=>$transaction->accountId));
            if(!$withdraw) {
                throw new SystemException('Withdraw account doesn\'t exist');
            }
            
            $withdraw->balance = bcsub($withdraw->balance, $transaction->amount);
            $transaction->verifyStatus = 'done';
            $transaction->details = json_encode(array(
                'txid' => $txid
            ));
            
            $transaction->update();
            $withdraw->update();
            $dbTransaction->commit();
        } catch (Exception $e) {
            $dbTransaction->rollback();
            Response::ResponseError($e->getMessage());
        }

        Response::ResponseSuccess();
    }
}