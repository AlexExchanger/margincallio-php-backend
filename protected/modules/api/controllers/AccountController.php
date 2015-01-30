<?php

class AccountController extends CController {

    private $user = null;
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(Yii::app()->user->isGuest) {
            Response::ResponseError('Access denied');
            return false;
        }

        $this->user = Yii::app()->user;
        
        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);
        
        return true;
    }
    
    public function actionGetWalletList() {
        $pair = Yii::app()->request->getParam('pair', 'BTC,USD');
        
        try {
            $accountInfo = Account::getAccountInfo($pair);
            Loger::logUser(Yii::app()->user->id, 'Requested wallets list');
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($accountInfo);
    }
    
    //type 0 = s to t, type 1 = t to s 
    public function actionTransferFunds() {
        $type = Yii::app()->request->getParam('type');
        $currency = Yii::app()->request->getParam('currency', false);
        $amount = Yii::app()->request->getParam('amount', false);
        
        if(!isset($type)) {
            Response::ResponseError('No type');
        }
        
        if(!in_array($currency, Yii::app()->params->supportedCurrency)) {
            Response::ResponseError('Currency doesn\'t support');
        }
        
        try {
            if ($currency == 'BTC') {
                if (!preg_match('~^\d+(\.\d{1,8})?$~', $amount)) {
                    throw new Exception('Wrong amount');
                }
            } elseif (!preg_match('~^\d+(\.\d{1,2})?$~', $amount)) {
                throw new Exception('Wrong amount');
            }
            
            Account::transferFunds($currency, $amount, $type);
            
            $logMessage = 'Transfer '.$currency.' funds from ';
            $logMessage .= ($type)? 'trade to safe. Amount: '.$amount:'safe to trade. Amount: '.$amount;
            Loger::logUser(Yii::app()->user->id, $logMessage, 'fundsTransferred');
        } catch(Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess();
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
        
        Response::ResponseSuccess($orders);
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
        
        Response::ResponseSuccess($orders);
        
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
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionGetTicket() {
        $ticketId = Yii::app()->request->getParam('ticketId');
        
        try {
            $ticket = Ticket::getByUser($ticketId, $this->user->id);
        } catch(Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        $messages = array();
        foreach($ticket->messages as $value) {
            $messages[] = array(
                'id' => $value->id,
                'createdBy' => $value->createdBy,
                'createdAt' => $value->createdAt,
                'text' => $value->text);
        }
        
        Response::ResponseSuccess(array('ticket'=>$ticket, 'messages'=>$messages));
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
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();   
    }
    
    public function actionGetOrders() {
        
        $filter = array(
            'types' => array('accepted'),
            'userId' => $this->user->id,
        );
        
        try {
            $orders = Order::getList($filter, $this->paginationOptions);
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($orders);
    }
    
    
}