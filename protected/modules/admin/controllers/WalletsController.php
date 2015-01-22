<?php

class WalletsController extends AdminController{
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }
        return true;
    }
    
    public function actionCreateHotWallet() {
        $currency = $this->getParam('currency', 'BTC');
        
        if(!Account::createHot($currency)) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionGetHotWallet() {
        $currency = $this->getParam('currency', 'BTC');
        $gateway = $this->getParam('gateway');
        
        try {
            $accounts = Account::getHot($currency, $gateway);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($accounts);
    }
       
}