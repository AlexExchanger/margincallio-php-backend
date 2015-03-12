<?php

class GatewayController extends AdminController {
    
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);
        
        return true;
    }
    
    public function actionAll() {
        try {
            $data = array(
                'currency' => $this->getParam('currency'),
                'type' => $this->getParam('type'),
            );
            
            $gateway = ExternalGateway::getList($data, $this->paginationOptions);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        Response::ResponseSuccess(array(
            'count' => count($gateway),
            'data' => $gateway
        ));
    }
    
    public function actionGetGateway() {
        try {
            $id = $this->getParam('id');
            $gateway = ExternalGateway::model()->findByPk($id);
            
            if(!$gateway) {
                throw new Exception();
            }
        } catch(Exception $e) {
            Response::ResponseError();
        }
        Response::ResponseSuccess($gateway);
    }
    
    public function actionApproveAddFunds() {
        
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
            $transactionId = $this->getParam('id', null);
            
            $transaction = TransactionExternal::model()->findByPk($transactionId);
            if(!$transaction) {
                throw new Exception('Wrong id parameter');
            }
            
            if($transaction->verifiedBy == Yii::app()->user->id) {
                throw new Exception('This user can\'t approve this transaction');
            }
            
            if($transaction->verifyStatus != 'pending') {
                throw new Exception('This transaction can\'t be updated');
            }
            
            $accountQuery = 'SELECT * FROM "account" WHERE id=:id FOR UPDATE';
            $account = Account::model()->findBySql($accountQuery, array(':id'=>$transaction->accountId));
            if(!$account) {
                throw new Exception('Account doesn\'t exist');
            }
            
            $account->balance = bcadd($account->balance, $transaction->amount);
            $account->update();
            
            $transaction->verifiedBy = Yii::app()->user->id;
            $transaction->verifyStatus = 'done';
            
            $transaction->update();
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess('Transaction approved, funds transfered');
    }
    
    
    public function actionAddFunds() {
        
        $data = array(
            'currency' => $this->getParam('currency', null),
            'userId' => $this->getParam('userId', null),
            'amount' => $this->getParam('amount', null),
            'details' => $this->getParam('details', null),
        );
        
        
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
            
            if(is_null($data['currency']) || $data['currency'] != 'EUR') {
                
                throw new Exception('Wrong currency parameter');
            }
            
            if(is_null($data['amount']) || !is_numeric($data['amount'])) {
                throw new Exception('Wrong amount parameter');
            }
            
            $account = Account::model()->findByAttributes(array(
                'userId' => $data['userId'],
                'type' => 'user.safeWallet'
            ));
            
            if(!$account) {
                throw new Exception('Account doesn\'t exist');
            }
            
            $transaction = new TransactionExternal();
            $transaction->gatewayId = 7;
            $transaction->type = false;
            $transaction->verifyStatus = 'pending';
            $transaction->verifiedBy = Yii::app()->user->id;
            $transaction->accountId = $account->id;
            $transaction->amount = $data['amount'];
            $transaction->createdAt = TIME;
            $transaction->currency = 'EUR';
            $transaction->details = $data['details'];
            
            
            if(!$transaction->save()) {
                throw new Exception('Transaction save error');
            }
            
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess('Transfer request successfuly added');
    }
    
    
    public function actionGrantFunds() {
        try {
            $data = array(
                'currency' => $this->getParam('currency'),
                'amount' => $this->getParam('amount'),
                'userId' => $this->getParam('userId'),
            );
            
            if(!ExternalGateway::grantFunds($data)) {
                throw new Exception();
            }
            
            $logMessage = 'Grant '.$data['amount'].' '.$data['currency'].'to user '.$data['userId'];
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'gatewayFundsTransfer');
        } catch(Exception $e) {
            Response::ResponseError();
        }
        Response::ResponseSuccess();
    }
    
}