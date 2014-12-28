<?php

class TransactionController extends AdminController {
    
    public $paginationOptions;

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->paginationOptions['limit'] = Yii::app()->request->getParam('limit', false);
        $this->paginationOptions['offset'] = Yii::app()->request->getParam('offset', false);
        $this->paginationOptions['sort'] = Yii::app()->request->getParam('sort', false);

        return true;
    }
    
    public function actionExternalTransactions() {
        $data = [
            'accountId' => Yii::app()->request->getParam('userId'),
            'dateFrom' => Yii::app()->request->getParam('dateFrom'),
            'dateTo' => Yii::app()->request->getParam('dateTo'),
            'status' => Yii::app()->request->getParam('status'),
        ];
        
        try {
            $transactions = TransactionExternal::getList($data, $this->paginationOptions);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($transactions);
    }
    
    public function actionAproveTransaction() {
        $id = Yii::app()->request->getParam('id');
        
        try {
            TransactionExternal::aproveTransaction($id);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionRejectTransaction() {
        $id = Yii::app()->request->getParam('id');
        
        try {
            TransactionExternal::rejectTransaction($id);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    
}