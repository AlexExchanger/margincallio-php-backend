<?php

class TransactionController extends AdminController {
    
    public $paginationOptions;

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);

        return true;
    }
    
    public function actionExternalIn() {
        $userId = $this->getParam('id');
        $status = $this->getParam('status', false);
        
        $accountCriteria = array(
            'userId' => $userId,
            'type' => 'user.safeWallet'
        );
        
        
        $userAccountList = Account::model()->findAllByAttributes($accountCriteria);
        
        $data  = array();
        foreach($userAccountList as $account) {
            $transactionCriteria = array(
                'accountId'=>$account->id,
                'type' => 1
            );
            
            if($status != false) {
                $transactionCriteria['status'] = $status;
            }
            
            $data[$account->currency] = TransactionExternal::getList($transactionCriteria, $this->paginationOptions);
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionExternalOut() {
        $userId = $this->getParam('id');
        $status = $this->getParam('status', false);
        
        $accountCriteria = array(
            'userId' => $userId,
            'type' => 'user.safeWallet'
        );
        
        $userAccountList = Account::model()->findAllByAttributes($accountCriteria);
        
        $data  = array();
        foreach($userAccountList as $account) {
            $transactionCriteria = array(
                'accountId'=>$account->id,
                'type' => 0
            );
            
            if($status != false) {
                $transactionCriteria['status'] = $status;
            }
            
            $data[$account->currency] = TransactionExternal::getList($transactionCriteria, $this->paginationOptions);
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionExternalTransactions() {
        $data = [
            'gatewayId' => $this->getParam('gatewayId'),
            'accountId' => $this->getParam('accountId'),
            'dateFrom' => $this->getParam('dateFrom'),
            'dateTo' => $this->getParam('dateTo'),
            'status' => $this->getParam('status'),
        ];
        
        try {
            $transactions = TransactionExternal::getList($data, $this->paginationOptions);
            
            $result = array(
                'count' => (isset($this->paginationOptions['total']))?$this->paginationOptions['total']:'',
                'data' => $transactions,
            );
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($result);
    }
    
    public function actionAproveTransaction() {
        $id = $this->getParam('id');
        
        try {
            TransactionExternal::aproveTransaction($id);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionRejectTransaction() {
        $id = $this->getParam('id');
        
        try {
            TransactionExternal::rejectTransaction($id);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionAll() {
        try {
            $result = Transaction::getList([], $this->paginationOptions);
            Transaction::getUsers($result);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess(array(
            'count' => isset($this->paginationOptions['total']) ? $this->paginationOptions['total'] : '',
            'data' => $result
        ));
    }
    
}