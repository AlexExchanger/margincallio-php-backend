<?php

class AccountController extends CController {

    private $user = null;
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(Yii::app()->user->isGuest) {
            print Response::ResponseError('Access denied');
            return false;
        }

        $this->user = Yii::app()->user;
        
        $this->paginationOptions['limit'] = Yii::app()->request->getParam('limit', false);
        $this->paginationOptions['offset'] = Yii::app()->request->getParam('offset', false);
        $this->paginationOptions['sort'] = Yii::app()->request->getParam('sort', false);
        
        return true;
    }
    
    public function actionGetWalletList() {
        
        //Example: BTC,USD
        $pair = Yii::app()->request->getParam('pair', 'BTC,USD');
        
        $accountList = Account::model()->findAllByAttributes(array(
            'userId'=>$this->user->id,
            'type'=> array('user.safeWallet'),
            ));
        
        if(!$accountList) {
            print Response::ResponseError('There are no wallets');
            exit;
        }
        
        $remoteAccountInfo = Account::getAccountInfo();
        
        $data = array();
        foreach($accountList as $key=>$value) {
            $data[] = array(
                'type' => 'safe',
                'currency' => $value->currency,
                'balance' => $value->balance, 
            );
        }
        
        $data[] = array(
            'type' => 'trade',
            'currency' => explode(',', $pair)[0],
            'balance' => (string)bcadd($remoteAccountInfo['firstAvailable'], 0)
        );
        
        $data[] = array(
            'type' => 'trade',
            'currency' => explode(',', $pair)[1],
            'balance' => (string)bcadd($remoteAccountInfo['secondAvailable'],0)
        );
        
        Loger::logUser(Yii::app()->user->id, 'Requested wallets list');
        print Response::ResponseSuccess($data);
    }
    
    //type 0 = s to t, type 1 = t to s 
    public function actionTransferFunds() {
        $type = Yii::app()->request->getParam('type');
        $currency = Yii::app()->request->getParam('currency', false);
        $amount = Yii::app()->request->getParam('amount', false);
        
        if(!isset($type)) {
            print Response::ResponseError('No type');
            exit();
        }
        
        if(!in_array($currency, Yii::app()->params->supportedCurrency)) {
            print Response::ResponseError('Currency doesn\'t support');
            exit();
        }
        
        try {
            if ($currency == 'BTC') {
                if (!preg_match('~^\d+(\.\d{1,8})?$~', $amount)) {
                    throw new Exception('Wrong amount');
                }
            } elseif (!preg_match('~^\d+(\.\d{1,2})?$~', $amount)) {
                throw new Exception('Wrong amount');
            }
            
            $logMessage = '';
            if($type) {
                Account::transferToSafe($currency, $amount);
                $logMessage = 'Transfer '.$currency.' funds from trade to safe. Amount: '.$amount;
            } else {
                Account::transferToTrade($currency, $amount);
                $logMessage = 'Transfer '.$currency.' funds from safe to trade. Amount: '.$amount;
            }
            Loger::logUser(Yii::app()->user->id, $logMessage, 'fundsTransferred');
        } catch(Exception $e) {
            print Response::ResponseError($e->getMessage());
            exit();
        }
        
        print Response::ResponseSuccess();
    }   
    
    public function actionGetActiveOrders() {
        
        try {
            $orders = Order::getActiveOrders($this->user->id);
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
        }
        
        print Response::ResponseSuccess($orders);
    }
    
    public function actionGetActiveСonditional() {
        
        try {
            $orders = Order::getActiveConditionalOrders($this->user->id);
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
        }
        
        print Response::ResponseSuccess($orders);
        
    }
    
    /* Ticket system */
    public function actionCreateTicket() {
        
        $text = Yii::app()->request->getParam('text');
        
        try {
            $ticket = Ticket::create(array(
                'title' => Yii::app()->request->getParam('title'),
                'department' => Yii::app()->request->getParam('department', 'general'),
            ), $text, $this->user->id);
            $logMessage = 'Create ticket with id: '.$ticket->id.', for '.$ticket->department.' department.';
            Loger::logUser(Yii::app()->user->id, $logMessage);
        } catch (Exception $e) {
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();
    }
    
    public function actionGetTicket() {
        $ticketId = Yii::app()->request->getParam('ticketId');
        
        try {
            $ticket = Ticket::getByUser($ticketId, $this->user->id);
        } catch(Exception $e) {
            print Response::ResponseError($e->getMessage());
            exit();
        }
        $messages = array();
        foreach($ticket->messages as $value) {
            $messages[] = array(
                'id' => $value->id,
                'createdBy' => $value->createdBy,
                'createdAt' => $value->createdAt,
                'text' => $value->text);
        }
        
        print Response::ResponseSuccess(array('ticket'=>$ticket, 'messages'=>$messages));
    }
    
    public function actionReplyForTicket() {
        
        $ticketId = Yii::app()->request->getParam('ticketId');
        $text = Yii::app()->request->getParam('text');
        
        try {
            $ticket = Ticket::getByUser($ticketId, $this->user->id);
            Ticket::modify($ticket, array(), $text, $this->user->id);
            
            $logMessage = 'Replying for ticket with id: '.$ticket->id.'.';
            Loger::logUser(Yii::app()->user->id, $logMessage);
        } catch (Exception $e) {
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();   
    }
    
    public function actionGetOrders() {
        
        $filter = array(
            'types' => array('accepted'),
            'userId' => $this->user->id,
        );
        
        try {
            $orders = Order::getList($filter, $this->paginationOptions);
        } catch (Exception $e) {
            print_r($e->getMessage()); die();
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess($orders);
    }
    
    
}