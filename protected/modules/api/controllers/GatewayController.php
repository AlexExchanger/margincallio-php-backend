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
        try {
            $gateways = ExternalGateway::model()->findAll();
            
            $data = array();
            foreach($gateways as $value) {
                $data[] = array(
                    'id' => $value->id,
                    'name' => $value->name,
                    'currency' => $value->currency,
                    'payment' => $value->payment,
                );
            }
        } catch(Exception $e) {
            Response::ResponseSuccess();
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionPayByGateway() {
        
        $paymentInformation = $this->getParam('payment');
        
        
    }
}