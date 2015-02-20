<?php

class AccountController extends MainController {

    private $user = null;
    public $paginationOptions;
    
    private $fullControl = array('alltrades', 'graphicsstat', 'getorderbook');
    
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
        
        $beginTime = Response::timestampToTick($this->getParam('begin', TIME - 804800));
        $endTime = Response::timestampToTick($this->getParam('end', TIME));
        
        $availableRange = array(            
            '1m' => 600000000,
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
        
        $candles = $candlesObject->getLast($beginTime, $endTime);
        
        if(count($candles) != 0) {
            Response::ResponseSuccess(array(
                'count' => count($candles),
                'data' => $candles
            ));
        }
        
        $timestampRange = $availableRange[$timeRange];

        $searchingCriteria = new CDbCriteria();
        $searchingCriteria->addBetweenCondition('"createdAt"', $beginTime, $endTime);
        $searchingCriteria->order = '"createdAt" DESC';
        
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
                        'open' => floatval(Response::bcScaleOut($trades['price'][0], 4)),
                        'close' => floatval(Response::bcScaleOut($trades['price'][count($trades['price'])-1], 4)),
                        'high' => floatval(Response::bcScaleOut(max($trades['price']), 4)),
                        'low' => floatval(Response::bcScaleOut(min($trades['price']), 4)),
                        'volume' => floatval(Response::bcScaleOut(array_sum($trades['volume']), 4)),
                        'timestamp' => Response::tickToTimestamp($currentRange)
                    );
                }                
                $currentRange -= $timestampRange;
                unset($trades);
                $trades = array('price'=>array(), 'volume'=>array());
            }
            
            $trades['price'][] = $trade->price;
            $trades['volume'][] = $trade->size;
        }

        Response::ResponseSuccess(array(
            'count' => count($candles),
            'data' => array_reverse($candles)
        ));
    }
    
    public function actionGetOrderBook() {
        try {
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_GET_DEPTH, 30));
            
            if(!isset($response[0]) || $response[0] != 0) {
                throw new Exception();
            }
            
            $bidOrderBook = array();
            foreach($response[5] as $value) {
                $bidOrderBook[] = array(
                    'amount' => Response::bcScaleOut($value[0], 4),
                    'rate' => Response::bcScaleOut($value[1], 4),
                    'sum' => Response::bcScaleOut(bcmul($value[0], $value[1]), 2)
                );
            }
            
            $askOrderBook = array();
            foreach($response[6] as $value) {
                $askOrderBook[] = array(
                    'amount' => Response::bcScaleOut($value[0], 4),
                    'rate' => Response::bcScaleOut($value[1], 4),
                    'sum' => Response::bcScaleOut(bcmul($value[0], $value[1]), 2)
                );
            }
            
            $result = array(
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
                    'id' => $value->id,
                    'amount' => Response::bcScaleOut($value->size),
                    'rate' => Response::bcScaleOut($value->price),
                    'timestamp' => Response::tickToTimestamp($value->createdAt),
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
            $message = '';
            if($e->getCode() == 10012) {
                $message = $e->getMessage();
            }
            
            Response::ResponseError($message);
        }
        
        Response::ResponseSuccess($accountInfo);
    }
    
    public function actionGetTransactions() {
        
        try {
            $filter = array(
                'user_from' => $this->user->id,
                'user_to' => $this->user->id,
                'user_or' => true,
            );
            
            $userAccounts = Account::getUserWallets($this->user->id);
            
            $transactions = Transaction::getList($filter, $this->paginationOptions); 
            
            $accountScope = array();
            foreach($userAccounts as $key=>$value) {
                $accountScope[] = $key;
            }
            
            $externalTransactions = TransactionExternal::model()->findAllByAttributes(array('accountId'=>$accountScope));
            
            $data = array();
            foreach($transactions as $value) {
                $data[$value->createdAt] = array(
                    'date' => Response::tickToTimestamp($value->createdAt),
                    'currency' => $value->currency,
                    'amount' => Response::bcScaleOut($value->amount),
                    'type' => ($userAccounts[$value->account_to]->type == 'user.safeWallet')? 'Internal in':'Internal out',
                    'status' => 'accepted',
                    'info' => '',
                );
            }
            
            foreach($externalTransactions as $value) {
                $data[$value->createdAt] = array(
                    'date' => Response::tickToTimestamp($value->createdAt),
                    'currency' => $value->currency,
                    'amount' => Response::bcScaleOut($value->amount),
                    'type' => ($value->type)? 'External In':'External Out',
                    'status' => $value->verifyStatus,
                    'info' => $value->details,
                );
            }
            
            $result = array();
            foreach($data as $value) {
                array_push($result, $value);
            }
            
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($result);
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
            $files = array();
            if (isset($_FILES) && count($_FILES) > 0) {
                foreach ($_FILES as $key => $value) {
                    $file = new File();
                    $file->fileName = $value['name'];
                    $file->fileSize = $value['size'];
                    $file->fileItem = new CUploadedFile($value['name'], $value['tmp_name'], $value['type'], $value['size'], $value['error']);
                    $file->uid = md5($this->user->id.$file->fileName.$file->fileSize.TIME);
                    $file->createdAt = TIME;
                    $file->createdBy = $this->user->id;
                    $file->entityType = 'ticket';

                    if ($file->save()) {
                        $path = Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file->uid;
                        $file->fileItem->saveAs($path);
                        $files[] = $file;
                    } else {
                        Response::ResponseError($file->getErrors());
                    }
                }
            }
            
            $ticket = Ticket::create(array(
                'title' => Yii::app()->request->getParam('title'),
                'department' => Yii::app()->request->getParam('department', 'general'),
            ), $text, $this->user->id, (count($files)>0)? $files:null);
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
            $files = array();
            if (isset($_FILES) && count($_FILES) > 0) {
                foreach ($_FILES as $key => $value) {
                    $file = new File();
                    $file->fileName = $value['name'];
                    $file->fileSize = $value['size'];
                    $file->fileItem = new CUploadedFile($value['name'], $value['tmp_name'], $value['type'], $value['size'], $value['error']);
                    $file->uid = md5($this->user->id.$file->fileName.$file->fileSize.TIME);
                    $file->createdAt = TIME;
                    $file->createdBy = $this->user->id;
                    $file->entityType = 'ticket';

                    if ($file->save()) {
                        $path = Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file->uid;
                        $file->fileItem->saveAs($path);
                        $files[] = $file;
                    } else {
                        Response::ResponseError($file->getErrors());
                    }
                }
            }
            
            $ticket = Ticket::getByUser($ticketId, $this->user->id);
            Ticket::modify($ticket, array(), $text, $this->user->id, (count($files)>0)? $files:null);
            
            $logMessage = 'Replying for ticket with id: '.$ticket->id.'.';
            Loger::logUser(Yii::app()->user->id, $logMessage);
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();   
    }
    
    public function actionGetOrders() {
        
        $filter = array(
            'userId' => $this->user->id,
        );
        
        try {
            $orders = Order::getList($filter, $this->paginationOptions);
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess(array(
            'count' => isset($this->paginationOptions['total']) ? $this->paginationOptions['total'] : '',
            'data' => $orders
        ));
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
                    'createdAt' => Response::tickToTimestamp($key[4])
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
    
    public function actionCreateFix() {
        try {
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_CREATE_FIX_ACCOUNT, $this->user->id));

            $data = array(
                'login' => $response[1],
                'password' => $response[2],
            );
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionChangeFixPassword() {
        $login = $this->getParam('login', NULL);
        try {
            if(is_null($login)) {
                throw new Exception();
            }
            
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_GENERATE_NEW_FIX_PASSWORD, $this->user->id, $login));

            if($response[0] != 0 || !isset($response[1])) {
                throw new Exception();
            }
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($response[1]);
    }
    
    public function actionGetFix() {
        try {
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_GET_FIX_ACCOUNT, $this->user->id));
            
            $data = array();
            foreach($response[1] as $key) {
                $data[] = array(
                    'login' => $key[0],
                    'password' => $key[2],
                    'is_active' => $key[3],
                    'createdAt' => Response::tickToTimestamp($key[4])
                );
            }
            
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionCancelFix() {
        $login = $this->getParam('login', NULL);
        try {
            if(is_null($login)) {
                throw new Exception();
            }
            
            $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_CANCEL_FIX_ACCOUNT, $this->user->id, $login));
            
            if($response[0] != 0) {
                throw new Exception();
            }
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
}