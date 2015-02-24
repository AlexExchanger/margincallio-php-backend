<?php

class GatewayController extends MainController { 
    
    private $user = null;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(Yii::app()->user->isGuest) {
            Response::GetResponseError('Access denied');
            return false;
        }
        
        $this->user = Yii::app()->user;

        return true;
    }
    
    public function actionall() {
        
        $currency = $this->getParam('currency', null);

        try {
            $filter = array('type'=>'user');
            if(!is_null($currency)) {
                $filter['currency'] = $currency; 
            }
            
            $gateways = ExternalGateway::model()->findAllByAttributes($filter);
            
            $data = array();
            foreach($gateways as $value) {
                $data[] = array(
                    'id' => $value->id,
                    'name' => $value->name,
                    'currency' => $value->currency,
                    'payment' => json_decode($value->payment, true),
                );
            }
        } catch(Exception $e) {
            Response::ResponseSuccess();
        }
        
        Response::ResponseSuccess(array(
            'count' => count($data),
            'data' => $data
        ));
    }
    
    public function actionPayByGateway() {
        $accountId = $this->getParam('accountId');
        $amount = $this->getParam('amount');
        $currency = $this->getParam('currency');
        $gatewayId = $this->getParam('id', 3);
        $paymentInformation = $this->getParam('payment');
        
        try {
            if(!isset($accountId) || !isset($amount)) {
                throw new Exception();
            }
            
            $data = array(
                'gatewayId' => $gatewayId,
                'accountId' => $accountId,
                'amount' => $amount,
                'currency' => $currency,
            );
            
            ExternalGateway::processPayment($data, $paymentInformation);
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionMake() {
        
        //BTC
        $gateway = GatewayFactory::create(2);
        $address = $gateway->transferTo(219);
        
        Response::ResponseSuccess($address);
    }
}