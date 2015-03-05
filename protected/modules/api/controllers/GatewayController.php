<?php

class GatewayController extends MainController { 
    
    private $user = null;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(Yii::app()->user->isGuest) {
            $this->actionPreflight();
            //Response::GetResponseError('Access denied');
            return false;
        }
        
        $this->user = Yii::app()->user;

        return true;
    }
    
    public function actionPreflight() {
        $content_type = 'application/json';
        $status = 200;

        header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
        header('Access-Control-Allow-Credentials: true');
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        }
        header('Content-type: ' . $content_type);
    }
    
    public function actionall() {
        
        $currency = $this->getParam('currency', null);

        try {
            $filter = array('type'=>'user');
            if(!is_null($currency)) {
                $filter['currency'] = $currency; 
            }
            
            $gateways = ExternalGateway::model()->findAllByAttributes($filter);
            
            $accounts = array();
            $data = array();
            foreach($gateways as $value) {
                $instance = GatewayFactory::create($value->id);
                if(!isset($accounts[$value->currency])) {
                    $accounts[$value->currency] = Account::getSafeByCurrency($value->currency);
                }
                $account = $accounts[$value->currency];
                $payment = $instance::getBillingMeta($value->payment, array('accountId'=>$account->id));
                
                $data[] = array(
                    'id' => $value->id,
                    'name' => $value->name,
                    'currency' => $value->currency,
                    'payment' => json_decode($payment, true),
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
      
        $accountId = $this->getPost('accountId');
        $amount = $this->getPost('amount');
        $currency = $this->getPost('currency');
        $gatewayId = $this->getPost('id', 3);
        $paymentInformation = $this->getPost('payment');
        $type = $this->getPost('type', null);
        
        
        try {
            if(!isset($accountId) || !isset($amount) || is_null($type)) {
                throw new Exception();
            }
            
            $type = ($type === 0 || $type === 'true')? 1:0;
            $data = array(
                'gatewayId' => $gatewayId,
                'accountId' => $accountId,
                'amount' => $amount,
                'currency' => $currency,
            );
            
            $result = ExternalGateway::processPayment($data, $paymentInformation, $type);
            if(!$result) {
                throw new Exception();
            }
            
            $message = 'Done';
            if($result == 'admin') {
                $message = 'Avaing for admin';
            }
            
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($message);
    }
    
    public function actionMake() {
        
        //BTC
        $gateway = GatewayFactory::create(2);
        $address = $gateway->transferTo(219);
        
        Response::ResponseSuccess($address);
    }
}