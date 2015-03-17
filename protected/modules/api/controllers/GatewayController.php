<?php

class GatewayController extends MainController { 
    
    private $user = null;
    private $fullControl = array('confurmwithdraw');
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(Yii::app()->user->isGuest && !in_array(mb_strtolower($action->id), $this->fullControl)) {
            $this->preflight();
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
            
            $data = array(
                'gatewayId' => $gatewayId,
                'accountId' => $accountId,
                'amount' => $amount,
                'currency' => $currency,
                'payment' => $paymentInformation,
                'type' => ($type == 'in')? 0:1,
            );
        
            $user = User::get(Yii::app()->user->id);
            if(!$user) {
                throw new Exception('User doesn\'t exist');
            }
            
            $confurm = UserConfurm::generateForUser(Yii::app()->user->id, $data);
            
            if(!MailSender::sendEmail('conformationOut', $user->email, array('user'=>Yii::app()->user->id, 'code'=>$confurm->code))) {
                throw new Exception('Error with confirmation sending');
            }
            
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess('Request sent to email');
    }
    
    public function actionMake() {
        
        //BTC
        $gateway = GatewayFactory::create(2);
        $address = $gateway->transferTo(219);
        
        Response::ResponseSuccess($address);
    }
    
    public function actionConfurmWithdraw() {
        $code = $this->getParam('code', null);
        $userId = $this->getParam('user', null);
        
        try {
            $confurm = UserConfurm::model()->findByAttributes(array(
                'code' => $code,
                'userId' => $userId,
                'used' => false
            ));
            
            if (!$confurm) {
                throw new Exception('Conformation doesn\'t exist');
            }

            $data = json_decode($confurm->details, true);
            
            $result = ExternalGateway::processPayment($data, $data['payment'], $data['type']);
            
            if (!$result) {
                throw new Exception('Payment can\'t be proccess');
            }

            $message = 'Done';
            if ($result == 'admin') {
                $message = 'Awaiting for admin';
            }

        } catch (Exception $e) {
            Response::ResponseError($e->getMessage());
        }

        Response::ResponseSuccess($message);
    }
    
}