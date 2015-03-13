<?php

class AccountsController extends AdminController {
    
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
    
    public function actionAll() {
        try {
            $result = Account::getList($this->paginationOptions);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess(array(
            'count' => isset($this->paginationOptions['total']) ? $this->paginationOptions['total'] : '',
            'data' => $result
        ));
    }
    
    
    public function actionSetFee() {
        $fee = $this->getParam('fee', null);
        $currency = $this->getParam('currency', null);
        
        try {
            
            $users = User::model()->findAllByAttributes(array(
                'type' => 'trader'
            ));
            
            $connection = new TcpRemoteClient();
            
            foreach($users as $value) {
                $connection->sendRequest(array(TcpRemoteClient::FUNC_SET_ACCOUNT_FEE, $value->id, $currency, $fee));
            }
        } catch(Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess('Success');
    }
    
}