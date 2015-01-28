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
        Response::ResponseSuccess($gateway);
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
        } catch(Exception $e) {
            Response::ResponseError();
        }
        Response::ResponseSuccess();
    }
    
}