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
    
}