<?php

class WalletController extends CController { 
    
    private $user = null;
    
    public function beforeAction($action) {
        if(Yii::app()->user->isGuest) {
            Response::GetResponseError('Access denied');
            return false;
        }
        
        $this->user = Yii::app()->user;

        return true;
    }
    
    private function btcCreateAddress() {
        $gateway = GatewayFactory::create('Btc', $this->user->id);
        
        if(!$gateway) {
            Response::ResponseError();
        }
        
        $address = $gateway->transferTo();
        Response::ResponseSuccess(array('address'=>$address));
    }

    public function actionReplenishWallet() {
        
        $currency = $this->getParam('currency');
        if(!$currency) {
            Response::ResponseError();
        }
        
        $currency = ucfirst(mb_strtolower($currency));
        if($currency == 'Btc') {
            return $this->btcCreateAddress();
        }
    }
    
}