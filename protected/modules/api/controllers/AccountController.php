<?php

class AccountController extends MainController {

    private $user = null;
    public $paginationOptions;
    
    private $fullControl = array('alltrades', 'graphicsstat');
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(Yii::app()->user->isGuest && !in_array(mb_strtolower($action->id), $this->fullControl)) {
            Response::ResponseError('Access denied');
            return false;
        }

        $this->user = Yii::app()->user;
        
        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);
        
        return true;
    }
    
    public function actionGraphicsStat() {
        
        $availableRange = array(
            '1m' => 60,
            '5m' => 300,
            '15m' => 900,
            '1h' => 3600,
        );
        
        $timeRange = $this->getParam('range', '15m');
        if(!isset($availableRange[$timeRange])) {
            Response::ResponseError();
        }
        
        $timestampRange = $availableRange[$timeRange];
        
        $lastWeek = TIME - 604800;

        $searchingCriteria = new CDbCriteria();
        $searchingCriteria->addCondition('"createdAt" > :lastMonth');
        $searchingCriteria->order = '"createdAt" DESC';
        $searchingCriteria->params = array(':lastMonth' => $lastWeek);

        $allTrades = Deal::model()->findAll($searchingCriteria);
        
        $candles = array();
        
        $currentRange = $allTrades[0]->createdAt - $timestampRange;
        $trades = array('price'=>array(), 'volume'=>array());
        foreach($allTrades as $trade) {
            if($trade->createdAt < $currentRange) {
                $candles[] = array(
                    'open' => $trades['price'][0],
                    'close' => $trades['price'][count($trades)-1],
                    'high' => max($trades['price']),
                    'low' => min($trades['price']),
                    'volume' => array_sum($trades['volume']),
                    'timestamp' => $currentRange
                );
                
                $currentRange -= $timestampRange;
                unset($trades);
            }
            
            $trades['price'][] = $trade->price;
            $trades['volume'][] = $trade->size;
        }

        Response::ResponseSuccess(array(
            'count' => count($candles),
            'data' => $candles
        ));
    }
    
    public function actionAllTrades() {
        
        if($this->paginationOptions['limit'] == false) {
            //magin number
            $this->paginationOptions['limit'] = 30;
        }
        
        try {
            $trades = Deal::getList(array(), $this->paginationOptions);
            $data = array();
            foreach($trades as $value) {
                $data[] = array(
                    'size' => $value->size,
                    'price' => $value->size,
                    'time' => $value->createdAt,
                );
            }
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess(array(
            'count' => (isset($this->paginationOptions))?$this->paginationOptions['total']:'',
            'data' => $data
        ));
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