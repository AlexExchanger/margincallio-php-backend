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
            '1m' => 6000000000,
            '5m' => 3000000000,
            '15m' => 9000000000,
            '1h' => 36000000000,
        );
        
        $timeRange = $this->getParam('range', '15m');
        $pair = $this->getParam('pair', 'BTCUSD');
        
        if(!isset($availableRange[$timeRange])) {
            Response::ResponseError();
        }
        
        $candlesObject = new Candles($pair, $timeRange);
        $lastWeek = Response::timestampToTick(TIME - 804800);
        
        $candles = $candlesObject->getLast($lastWeek);
        
        if(count($candles) != 0) {
            Response::ResponseSuccess(array(
                'count' => count($candles),
                'data' => $candles
            ));
        }
        
        $timestampRange = $availableRange[$timeRange];

        $searchingCriteria = new CDbCriteria();
        $searchingCriteria->addCondition('"createdAt" > :lastMonth');
        $searchingCriteria->order = '"createdAt" DESC';
        $searchingCriteria->params = array(':lastMonth' => $lastWeek);

        $allTrades = Deal::model()->findAll($searchingCriteria);
        
        if(count($allTrades) <= 0) {
            Response::ResponseError();
        }
        
        $currentRange = $allTrades[0]->createdAt - $timestampRange;
        $trades = array('price'=>array(), 'volume'=>array());
        foreach($allTrades as $trade) {
            if($trade->createdAt < $currentRange) {
                if(count($trades['price']) > 0) {
                    $candles[] = array(
                        'open' => $trades['price'][0],
                        'close' => $trades['price'][count($trades['price'])-1],
                        'high' => max($trades['price']),
                        'low' => min($trades['price']),
                        'volume' => array_sum($trades['volume']),
                        'timestamp' => Response::tickToTimestamp($currentRange)
                    );
                }                
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
    
    public function actionGetOrderBook() {
        try {
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_GET_DEPTH, 30));
            
            if(!isset($response[0]) || $response[0] != 0) {
                throw new Exception();
            }
            
            $askOrderBook = array();
            foreach($response[5] as $value) {
                $askOrderBook[] = array(
                    'size' => $value[0],
                    'price' => $value[1],
                    'price_currency' => Response::bcScaleOut(bcmul($value[0], $value[1]))
                );
            }
            
            $bidOrderBook = array();
            foreach($response[6] as $value) {
                $bidOrderBook[] = array(
                    'size' => $value[0],
                    'price' => $value[1],
                    'price_currency' => Response::bcScaleOut(bcmul($value[0], $value[1]))
                );
            }
            
            $result = array(
                'bidVolume' => $response[1],
                'askVolume' => $response[2],
                'bidCount' => $response[3],
                'askCount' => $response[4],
                'bid' => $bidOrderBook,
                'ask' => $askOrderBook,
            );
            
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($result);
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
                    'side' => $value->side,
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
    
    public function actionGenerateApiKey() {
        $rights = $this->getParam('rights', false);
        $rightsParam = ($rights === 0 || $rights === 'true')? 1:0;
        
        try {
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_GENERATE_API_KEY, $this->user->id, $rightsParam));
            
            $data = array(
                'key' => $response[1],
                'secret' => $response[2],
            );
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionGetApiKeys() {
        try {
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_GET_API_KEY, $this->user->id));
            
            $data = array();
            foreach($response[1] as $key) {
                $data[] = array(
                    'rights' => $key[0],
                    'key' => $key[1],
                    'secret' => $key[2],
                    'createdAt' => floor($key[4]/10000000)
                );
            }
            
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionCancelApiKey() {
        $key = $this->getParam('key', NULL);
        try {
            if(is_null($key)) {
                throw new Exception();
            }
            
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_CANCEL_API_KEY, $key));
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($response);
    }
    
    public function actionGenerateFix() {
        
    }
    
    public function actionGetFix() {
        
    }
    
    public function actionCancelFix() {
        
    }
    
    
}