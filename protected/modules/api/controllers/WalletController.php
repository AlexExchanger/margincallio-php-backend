<?php

class WalletController extends CController { 
    
    private $user = null;
    
    public function beforeAction($action) {
        if(Yii::app()->user->isGuest) {
            print Response::ResponseError('Access denied');
            return false;
        }
        
        $this->user = Yii::app()->user;

        return true;
    }
    
    private function btcCreateAddress() {
        $gateway = GatewayFactory::create('Btc', $this->user->id);
        
        if(!$gateway) {
            print Response::ResponseError();
            exit();
        }
        
        $address = $gateway->transferTo();
        print Response::ResponseSuccess(array('address'=>$address));
    }

    public function actionReplenishWallet() {
        
        $currency = Yii::app()->request->getParam('currency');
        if(!$currency) {
            print Response::ResponseError();
            exit();
        }
        
        $currency = ucfirst(mb_strtolower($currency));
        if($currency == 'Btc') {
            return $this->btcCreateAddress();
        }
    }
    
}