<?php

class AccountController extends MainController {

    private $user = null;
    public $paginationOptions;
    
    private $fullControl = array('alltrades', 'graphicsstat', 'getorderbook', 'createticket');
    
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
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
    
    public function actionGetWallet() {
        
        $id = $this->getParam('id', null);
        
        try {
            if(is_null($id)) {
                throw new Exception('id - parameter invalid');
            }
            
            $data = Account::getAccountInfoOne($id);
        } catch(Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionGetWalletList() {
        $pair = Yii::app()->request->getParam('pair', 'BTC,USD');
        
        try {
            $accountInfo = Account::getAccountInfo($pair);
            Loger::logUser(Yii::app()->user->id, 'Requested wallets list');
        } catch(Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            $message = '';
            if($e->getCode() == 10012) {
                $message = $e->getMessage();
            }
            
            Response::ResponseError($message);
        }
        
        Response::ResponseSuccess($accountInfo);
    }
    
    public function actionGetTransactions() {
        
        $accountId = $this->getParam('accountId', null);
        
        $inputData = array(
            'filterType' => $this->getParam('filterType', 'external'),
            'criteria' => $this->getParam('filterCriteria', ''),
        );
        
        try {
            if(is_null($accountId)) {
                throw new Exception();
            }
            
            $account = Account::get($accountId);
            
            if(!$account || $account->userId != $this->user->id) {
                throw new Exception('Account for given user doesn\'t exist');
            }
            
            $filter = array(
                'accountId' => $accountId,
                'account_from' => $accountId,
                'account_to' => $accountId,
                'account_or' => true,
                'balance_criteria' => $inputData['criteria'],
            );
            
            $data = array();
            if($inputData['filterType'] == 'internal') {
                $transactions = Transaction::getList($filter, $this->paginationOptions);
                foreach($transactions as $value) {
                    $data[$value->createdAt] = array(
                        'date' => Response::tickToTimestamp($value->createdAt),
                        'currency' => $value->currency,
                        'amount' => Response::bcScaleOut($value->amount),
                        'type' => ($value->side == false)? 'Internal in':'Internal out',
                        'status' => 'accepted',
                        'info' => '',
                    );
                }
            } else {
                $externalTransactions = TransactionExternal::getList($filter, $this->paginationOptions);
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess();
    }   
    
    public function actionGetActiveOrders() {
        try {
            $orders = Order::getActiveOrders($this->user->id);
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
        }
        
        Response::ResponseSuccess($orders);
    }
    
    public function actionGetActiveСonditional() {
        
        try {
            $orders = Order::getActiveConditionalOrders($this->user->id);
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
        }
        
        Response::ResponseSuccess($orders);
        
    }
    
    private function preflight() {
        $content_type = 'application/json';
        $status = 200;

        header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
        header('Access-Control-Allow-Credentials: true');
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        }
        header('Content-type: ' . $content_type);
    }
    
    /* Ticket system */
    public function actionCreateTicket() {
        
        $text = $this->getParam('text', null);
        
        if (isset($_FILES) && count($_FILES) > 0) { 
            //files
            $files = array();
            try {
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
                        $files[] = $file->id;
                    } else {
                        Response::ResponseError($file->getErrors());
                    }
                }
            } catch(Exception $e) {
                Response::ResponseError($e->getMessage());
            }
            
            $logMessage = 'Upload files with id: '.implode(',', $files);
            Loger::logUser(Yii::app()->user->id, $logMessage);
            Response::ResponseSuccess($files);
        } elseif(!is_null($text)) {
            //ticket
            $ticket = Ticket::create(array(
                'title' => $this->getParam('title'),
                'department' => $this->getParam('department', 'general'),
                'files' => $this->getParam('files', null)
            ), $text, $this->user->id, null);
            $logMessage = 'Create ticket with id: '.$ticket->id.', for '.$ticket->department.' department.';
            Loger::logUser(Yii::app()->user->id, $logMessage);
            
            Response::ResponseSuccess();
        } else {
            //headers
            $this->preflight();
        }
    }
    
    public function actionGetAllTickets() {
        $userId = Yii::app()->user->id;
        $status = $this->getParam('status', null);
        $department = $this->getParam('department', null);
        try {
            if(!$userId) {
                throw new Exception();
            }
            
            $filters = array('userId' => $userId);
            if(!is_null($status)) {
                $filters['status'] = $status;
            }
            
            if(!is_null($department)) {
                $filters['department'] = $department;
            }
            
            $tickets = Ticket::getList($filters, $this->paginationOptions);
            
            $data = array();
            foreach($tickets as $ticket) {
                $messages = array();
                foreach ($ticket->messages as $value) {
                    $currentFilesObjects = array();
                    if(!is_null($value->files)) {
                        $currentFiles = explode(',', $value->files);
                        foreach($currentFiles as $oneFile) {
                            if(!isset($allFiles[$oneFile])) {
                                $file = File::model()->findByPk($oneFile);
                                if(!$file) {continue;}
                                $allFiles[$oneFile] = array(
                                    'url' => '/files/'.$file->uid,
                                    'uid' => $file->uid,
                                    'type' => $file->entityType
                                );
                            }
                            $currentFilesObjects[] = $allFiles[$oneFile];
                        }
                    }
                    
                    $messages[] = array(
                        'id' => $value->id,
                        'createdBy' => ($value->createdBy == $userId)? $userId: null,
                        'createdAt' => $value->createdAt,
                        'text' => $value->text,
                        'files' => $currentFilesObjects
                    );
                    
                    if(count($messages) > 2) {
                        break;
                    }
                }
                
                $data[] = array(
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'createdBy' => ($ticket->createdBy == $userId)? $userId: null,
                    'createdAt' => $ticket->createdAt,
                    'status' => $ticket->status,
                    'department' => $ticket->department,
                    'updatedAt' => $ticket->updatedAt,
                    'updatedBy' => ($ticket->updatedBy == $userId)? $userId: null,
                    'messageCount' => $ticket->messageCount,
                    'userId' => $ticket->userId,
                    'importance' => $ticket->importance,
                    'messages' => $messages
                );
            }
            
        } catch(Exception $e) {
            Response::ResponseError();
        }
        Response::ResponseSuccess(array(
            'count' => count($tickets),
            'data' => $data
        ));
    }
    
    public function actionGetTicket() {
        $ticketId = Yii::app()->request->getParam('ticketId');

        try {
            $ticket = Ticket::getByUser($ticketId, $this->user->id);
            if($ticket->createdBy != Yii::app()->user->id) {
                $ticket->createdBy = null;
            }
            if($ticket->updatedBy != Yii::app()->user->id) {
                $ticket->updatedBy = null;
            }
            
            $messages = array();
            foreach ($ticket->messages as $value) {
                $currentFilesObjects = array();
                if(!is_null($value->files)) {
                    $currentFiles = explode(',', $value->files);
                    foreach($currentFiles as $oneFile) {
                        if(!isset($allFiles[$oneFile])) {
                            $file = File::model()->findByPk($oneFile);
                            if(!$file) {continue;}
                            $allFiles[$oneFile] = array(
                                'url' => '/files/'.$file->uid,
                                'uid' => $file->uid,
                                'type' => $file->entityType
                            );
                        }
                        $currentFilesObjects[] = $allFiles[$oneFile];
                    }
                }

                $messages[] = array(
                    'id' => $value->id,
                    'createdBy' => ($value->createdBy == Yii::app()->user->id)? $value->createdBy: null,
                    'createdAt' => $value->createdAt,
                    'text' => $value->text,
                    'files' => $currentFilesObjects
                );
            }
        } catch (Exception $e) {
            Response::ResponseError($e->getMessage());
        }

        Response::ResponseSuccess(array('ticket' => $ticket, 'messages' => $messages));
    }

    public function actionReplyForTicket() {
        
        $ticketId = Yii::app()->request->getParam('ticketId');
        $text = $this->getParam('text', null);
        
        if (isset($_FILES) && count($_FILES) > 0) { 
            //files
            $files = array();
            try {
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
                        $files[] = $file->id;
                    } else {
                        Response::ResponseError($file->getErrors());
                    }
                }
            } catch(Exception $e) {
                Response::ResponseError($e->getMessage());
            }
            
            $logMessage = 'Upload files with id: '.implode(',', $files);
            Loger::logUser(Yii::app()->user->id, $logMessage);
            Response::ResponseSuccess($files);
        } elseif(!is_null($text)) {
            //ticket
            $ticket = Ticket::getByUser($ticketId, $this->user->id);
            Ticket::modify($ticket, array(
                'status' => $this->getParam('status', null),
                'files' => $this->getParam('files', null),
            ), $text, $this->user->id, null);

            $logMessage = 'Replying for ticket with id: ' . $ticket->id . '.';
            Loger::logUser(Yii::app()->user->id, $logMessage);
            
            $msg = TicketMessage::getLastMessages($ticketId, 1);
            if(isset($msg) && isset($msg[0])) {
                Response::ResponseSuccess($msg[0]);
            }
            Response::ResponseSuccess();
        } else {
            //headers
            $this->preflight();
        }
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
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
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionGetTrades() {
        try {
            $orders = Order::getOrdersWithDeals(array(
                'userId' => Yii::app()->user->id
            ), $this->paginationOptions);
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($orders);
    }
    
}