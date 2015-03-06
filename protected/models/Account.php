<?php

class Account extends CActiveRecord {

    private static $systemUserId = 0;
    
    public static $currencyOptions = ['LTC', 'DOGE', 'BTC', 'USD', 'EUR'];
    
    public static $systemTypeOptions = [
        'system.gateway.external.universe', // отдано наружу / принято снаружи (сумма)
        'system.gateway.external.universe.unknown', // неизвестные платежи
        'system.gateway.external', // всегда плюс, реальный внешний кошелек/банк
        'system.gateway.internal', // оборот по шлюзу (при внешнем поступлении минус)
        'system.gateway.cold',
        'system.gateway.hot',
        'system.gateway.grant'
    ];
    
    public static $typeOptions = [
        'user.trading', // для торговли на бирже, сюда прямо ввод снаружи
        'user.safeWallet', // Safe счет пользователя
        'user.withdrawWallet', // для вывода денег
    ];
    public static $statusOptions = ['opened', 'closed', 'blocked'];

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'account';
    }

    public function rules() {
        return array(
            array('currency', 'filter', 'filter' => 'strtoupper'),
            array('currency', 'in', 'allowEmpty' => false, 'range' => self::$currencyOptions, 'strict' => true),
            array('type', 'in', 'allowEmpty' => false, 'range' => array_merge(self::$typeOptions, self::$systemTypeOptions), 'strict' => true),
            array('status', 'in', 'allowEmpty' => false, 'range' => self::$statusOptions, 'strict' => true),
        );
    }

    public static function get($id) {
        $account = null;
        if (is_numeric($id)) {
            $account = Account::model()->findByPk($id);
        } elseif (Guid::validate($id)) {
            $account = Account::model()->findByAttributes(['guid' => $id]);
        }

        return $account;
    }
    
    public static function getUserByAccount($accountId) {
        return User::model()->findAllByPk($accountId);
    }

    public static function getMany(array $ids) {
        return $ids ? self::model()->findAllByPk($ids) : [];
    }
    
    public static function getList(array &$pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = new CDbCriteria();
        $pagination['total'] = (int)self::model()->count($criteria);
        if ($limit) {
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }
        
        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return self::model()->findAll($criteria);
    }

    public static function create(array $data) {
        $account = new self();
        $account->currency = ArrayHelper::getFromArray($data, 'currency');
        $account->status = ArrayHelper::getFromArray($data, 'status');
        $account->type = ArrayHelper::getFromArray($data, 'type');
        $account->creditLimit = ArrayHelper::getFromArray($data, 'creditLimit', 0);
        $account->userId = ArrayHelper::getFromArray($data, 'userId');
        $account->tickerId = 1;

        $account->createdAt = TIME;
        $account->balance = 0;
        $account->guid = Guid::generate();
        $account->publicId = null;

        //gateway account
        if (strpos($account->type, 'system.gateway.') === 0) {
            $account->userId = null;
            $account->tickerId = null;
            //для пополнения счета шлюза
            if ($account->type == 'system.gateway.external') {

            }
        } //system account
        elseif (strpos($account->type, 'system.ticker.') === 0) {
            $account->userId = null;
            if (!is_numeric($account->tickerId)) {
                $account->addError('tickerId', 'Ticker not found');
            }
        } // users account
        else {
            $account->tickerId = null;
            $user = User::get($account->userId);
            if (!$user) {
                $account->addError('userId', 'User not found');
            }
        }
        if (!$account->validate(null, false)) {
            throw new ExceptionUserVerification();
        }
        try {
            if (!$account->save(false)) {
                throw new ExceptionUserSave();
            }
            $account->save();
        }
        catch (Exception $e) {
            throw $e;
        }

        return $account;
    }

    public function setBalance($balance) {
        if ($this->balance === $balance) {
            return false;
        }
        $this->balance = $balance;

        if (!$this->save(true, ['balance'])) {
            throw new ModelException('Balance was not updated', $this->getErrors());
        }

        return true;
    }
    
    private static function createSystemAccount($currency) {
        
        $accountList = array();
        
        foreach(self::$systemTypeOptions as $wallet) {
            $account = new Account();
            $account->currency = $currency;
            $account->status = 'opened';
            $account->userId = self::$systemUserId;
            $account->createdAt = TIME;
            $account->type = $wallet;
            $account->guid = Guid::generate();
            $account->save();
            array_push($accountList, $account);
        }
        return $accountList;
    }
    public static function getSystemTradeAccount($currency) {
        if(!in_array($currency, self::$currencyOptions)) {
            return false;
        }
        
        $accounts = Account::model()->findAllByAttributes(array(
            'type'=>array(
                'system.gateway.external.universe',
                'system.gateway.external.universe.unknown',
                'system.gateway.external',
                'system.gateway.internal'
            ),
            'userId'=>self::$systemUserId,
            'currency'=>$currency,
            
        ));
        
        if(!count($accounts)) {
            $accounts = self::createSystemAccount($currency);
        }
        
        $data = array();
        foreach($accounts as $value) {
            $data[$value->type] = $value;
        }
        
        return $data;
    }
    
    public static function getSystemAccount($currency) {
        if(!in_array($currency, self::$currencyOptions)) {
            return false;
        }
        
        $accounts = Account::model()->findAllByAttributes(array(
            'type'=>self::$systemTypeOptions,
            'userId'=>self::$systemUserId,
            'currency'=>$currency
        ));
        
        if(!count($accounts)) {
            $accounts = self::createSystemAccount($currency);
        }
        
        $data = array();
        foreach($accounts as $value) {
            $data[$value->type] = $value;
        }
        
        return $data;
    }
    
    //TODO: rewrite this   
    private static function getAccountPair($userId, $currency) {
        $accounts = self::model()->findAllByAttributes(array(
            'userId' => $userId,
            'currency' => $currency,
            'type' => array('user.trading', 'user.safeWallet')
        ));
        
        if(!$accounts) {
            throw new ExceptionNoAccount();
        }
        
        $wallets = array();
        foreach($accounts as $wallet) {
            $wallets[$wallet->type] = $wallet;
        }
        
        return $wallets;
    }
        
    private static function createTransaction($wallets, $amount, $type, $currency) {
        
        $accountFrom = ($type)?$wallets['user.trading']:$wallets['user.safeWallet'];
        $accountTo = ($type)?$wallets['user.safeWallet']:$wallets['user.trading'];
        
        $transaction = new Transaction();
        $transaction->account_from = $accountFrom->id;
        $transaction->account_to = $accountTo->id;
        $transaction->amount = $amount;
        $transaction->createdAt = TIME;
        $transaction->currency = $currency;
        $transaction->user_from = Yii::app()->user->id;
        $transaction->user_to = Yii::app()->user->id;
        $transaction->side = $type;
        
        if (!$transaction->save()) {
            throw new SystemException('Something wrong with transaction creating', $transaction->getErrors());
        }
    }
    
    //type 0 = s to t, type 1 = t to s
    public static function transferFunds($currency, $amount, $type) {
        $walletFrom = ($type)? 'user.trading':'user.safeWallet';
        $walletTo = (!$type)? 'user.trading':'user.safeWallet';
        
        $dbTransaction = Yii::app()->db->beginTransaction();
        $user = Yii::app()->user;
        
        try {
            $wallets = self::getAccountPair($user->id, $currency);

            $compare = bccomp($wallets[$walletFrom]->balance, $amount);
            if($compare < 0) {
                throw new ExceptionNoMoney();
            }

            $wallets[$walletFrom]->balance = bcsub($wallets[$walletFrom]->balance, $amount);
            $wallets[$walletTo]->balance = bcadd($wallets[$walletTo]->balance, $amount);

            $systemsAccounts = self::getSystemTradeAccount($currency);
            if($type) {
                $systemsAccounts['system.gateway.internal']->balance = bcsub($systemsAccounts['system.gateway.internal']->balance, $amount);
            } else {
                $systemsAccounts['system.gateway.internal']->balance = bcadd($systemsAccounts['system.gateway.internal']->balance, $amount);
            }

            $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $resultCore = null;
            if($type) {
                $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_REPLENISH_SAFE_ACCOUNT, $user->id, mb_strtolower($currency), $amount));
            } else {
                $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_REPLENISH_TRADE_ACCOUNT, $user->id, mb_strtolower($currency), $amount));
            }

            if($resultCore[0] != 0) {
                $e = new ExceptionTcpRemoteClient();
                $e->errorType = $resultCore[0];
                throw $e;
            }
            
            if(!$wallets[$walletFrom]->save() || !$wallets[$walletTo]->save() || !$systemsAccounts['system.gateway.internal']->save()) {
                throw new ExceptionAccount(); 
            }

            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }
        
        self::createTransaction($wallets, $amount, $type, $currency);
        
        return true;
    }

    public static function getSafeByCurrency($currency) {
        $userId = Yii::app()->user->id;
        if(!$userId) {
            return false;
        }
        
        return self::model()->findByAttributes(array(
            'userId' => $userId,
            'type'=> array('user.safeWallet'),
            'currency'=>$currency,
        ));
    }
    
    public static function getAccountBalance($currency1, $currency2) {
        $user = Yii::app()->user;
        if(!$user) {
            throw new Exception('User doesn\'t exist');
        }
        
        if(!in_array($currency1, Yii::app()->params->supportedCurrency) || !in_array($currency2, Yii::app()->params->supportedCurrency)) {
            throw new Exception('Currency doesn\'t support');
        }
        
        $data = array();

        //safe accounts
        $safe = Account::model()->findAllByAttributes(array(
            'userId' => $user->id,
            'type' => array('user.safeWallet'),
            'currency' => array($currency1, $currency2)
        ));
        
        $safeAccounts = array();
        foreach($safe as $account) {
            $safeAccounts[$account->currency] = $account;
        }
        
        $data['safe'] = array(
            'first' => Response::bcScaleOut($safeAccounts[$currency1]->balance, 8),
            'second' => Response::bcScaleOut($safeAccounts[$currency2]->balance, 8)
        );
        
        
        //trade accounts
        $connector = new TcpRemoteClient();
        $responseInfo = array();
        foreach(array($currency1, $currency2) as $currency) {
            $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_ACCOUNT_BALANCE, $user->id, mb_strtolower($currency)));
            if(count($resultCore) <= 0 || !isset($resultCore[0]) || ($resultCore[0] != 0)) {
                throw new Exception("User doesn't verified", 10012);
            }
            $responseInfo[$currency] = array(
                'available' => $resultCore[1],
                'blocked' => $resultCore[2],
            );
        }
        
        $data['trade'] = array(
            'first' => Response::bcScaleOut(bcadd($responseInfo[$currency1]['available'], $responseInfo[$currency1]['blocked']) , 6),
            'second' => Response::bcScaleOut(bcadd($responseInfo[$currency2]['available'], $responseInfo[$currency2]['blocked']) , 6)
        );
        
        $data['trade_available'] = array(
            'first' => Response::bcScaleOut($responseInfo[$currency1]['available'], 6),
            'second' => Response::bcScaleOut($responseInfo[$currency2]['available'], 6),
        );
        
        
        return $data;
    }
    
    public static function getAccountInfo() {
        $user = Yii::app()->user;
        if(!$user) {
            return false;
        }
        
        $currencies = Account::getSupportedCurrency();
        
        $accountList = Account::model()->findAllByAttributes(array(
            'userId'=>$user->id,
            'type'=> array('user.safeWallet'),
        ));
        
        $tradeAccountList = Account::model()->findAllByAttributes(array(
            'userId'=>$user->id,
            'type'=> array('user.trading'),
        ));
        
        $withdrawalAccountList = Account::model()->findAllByAttributes(array(
            'userId' => $user->id,
            'type' => array('user.withdrawWallet')
        ));
        
        $tradeAccount = array();
        foreach($tradeAccountList as $value) {
            $tradeAccount[$value->currency] = $value; 
        }
        
        $withdrawalAccount = array();
        foreach($withdrawalAccountList as $value) {
            $withdrawalAccount[$value->currency] = $value; 
        }
        
        $connector = new TcpRemoteClient();
        
        $remoteAccountInfo = array();
        foreach($currencies['derived'] as $currency) {
            $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_ACCOUNT_BALANCE, $user->id, mb_strtolower($currency)));
            if(count($resultCore) <= 0 || !isset($resultCore[0]) || ($resultCore[0] != 0)) {
                throw new Exception("User doesn't verified", 10012);
            }
            
            $remoteAccountInfo[$currency] = array(
                'Available' => $resultCore[1],
                'Blocked' => $resultCore[2],
            );
        }
        
        //Query for getting account parameter
        try {
            $resultCore = $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_ACCOUNT_INFO, $user->id));
            $accountInfo = array(
                'maxLeverage' => $resultCore[1],
                'mcLevel' => $resultCore[2],
                'flLevel' => $resultCore[3],
                'equity' => $resultCore[4],
                'margin' => $resultCore[5],
                'freeMargin' => $resultCore[6],
                'marginLevel' => $resultCore[7],
                'marginCall' => $resultCore[8],
                'suspended' => $resultCore[9],
            );
        } catch(Exception $e) {
            if ($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
        }
        
        $data = array();
        foreach($accountList as $value) {
            $data[] = array(
                'id' => $value->id,
                'type' => 'safe',
                'currency' => trim($value->currency),
                'balance' => Response::bcScaleOut($value->balance),
                'hold' => isset($withdrawalAccount[$value->currency])? ''.Response::bcScaleOut($withdrawalAccount[$value->currency]->balance):'0',
            );
        }
        
        foreach($remoteAccountInfo as $currency => $account) {
            $id = isset($tradeAccount[$currency])? $tradeAccount[$currency]->id:'';
            $data[] = array(
                'id' => $id,
                'type' => 'trade',
                'currency' => $currency,
                'balance' => (string)Response::bcScaleOut(bcadd($account['Available'], 0)),
                'hold' => (string)Response::bcScaleOut(bcadd($account['Blocked'], 0))
            );
        }
        
        /*$funds = array(
            'trade' => array(
                'first' => Response::bcScaleOut(bcadd($remoteAccountInfo['firstAvailable'], $remoteAccountInfo['firstBlocked']), 8),
                'second' => Response::bcScaleOut(bcadd($remoteAccountInfo['secondAvailable'], $remoteAccountInfo['secondBlocked']), 8)
            ), 
            'trade_available' => array(
                'first' => Response::bcScaleOut($remoteAccountInfo['firstAvailable'], 8),
                'second' => Response::bcScaleOut($remoteAccountInfo['secondAvailable'], 8)
            ),
            'safe' => array(
                'first' => Response::bcScaleOut($accountList[0]->balance, 8),
                'second' => Response::bcScaleOut($accountList[1]->balance, 8),
            )
        );*/
        $accountInfo['wallets'] = $data;
   
        return $accountInfo;
    }
    
    public static function getAccountInfoOne($id) {
        $user = Yii::app()->user;
        if(!$user) {
            return false;
        }
        
        $account = Account::model()->findByAttributes(array(
            'userId'=>$user->id,
            'id'=> $id,
        ));
        
        if(!$account) {
            throw new Exception('Account doesn\'t exist');
        }
        
        $wallet = array();
        $currency = $account->currency;
        
        if($account->type == 'user.trading') {
            $connector = new TcpRemoteClient();
            $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_ACCOUNT_INFO, $user->id));

            if(count($resultCore) <= 0 || !isset($resultCore[0]) || ($resultCore[0] != 0)) {
                throw new Exception("User doesn't verified", 10012);
            }

            $remoteAccountInfo = array(
                'firstAvailable' => $resultCore[1],
                'firstBlocked' => $resultCore[2],
                'secondAvailable' => $resultCore[3],
                'secondBlocked' => $resultCore[4],
            );
            
            $wallet = array(
                'id' => $account->id,
                'type' => 'trade',
                'currency' => $account->currency,
                'balance' => (string)bcadd((($account->currency == $pairArray[0])?  $remoteAccountInfo['firstAvailable']:$remoteAccountInfo['secondAvailable']), 0, 6), 
                'hold' => (string)bcadd((($account->currency == $pairArray[0])?  $remoteAccountInfo['firstBlocked']:$remoteAccountInfo['secondBlocked']), 0, 6), 
            );
            
        } else {
            $withdrawal = Account::model()->findByAttributes(array(
                'userId'=>$user->id,
                'currency'=>$account->currency,
                'type' => array('user.withdrawWallet'),
            ));
            
            $holdValue = '0';
            if($withdrawal) {
                $holdValue = (string)Response::bcScaleOut($withdrawal->balance);
            }
            
            $wallet = array(
                'id' => $account->id,
                'type' => 'safe',
                'currency' => $account->currency,
                'balance' => (string)Response::bcScaleOut($account->balance), 
                'hold' => $holdValue
            );
        }
        
        return $wallet;
    }
    
    
    //external methods
    public static function transferToExternal($currency, $userId, $amount) {
        $currencyName = mb_strtolower($currency);
        
        $userAccount = Account::model()->findByAttributes(array(
            'userId' => $userId,
            'currency' => $currency,
            'type' => 'user.safeWallet'
        ));
        
        if(!$userAccount || (bccomp($userAccount, $amount)<0)) {
            return false;
        }
        
        $externalGateway = GatewayFactory::create($currencyName);
        $externalResult = $externalGateway->transferTo($address, $amount);
        
        if(!$externalResult) {
            return false;
        }
        
        $systemAccount = self::getSystemAccount($currency);
        $systemAccount['system.gateway.external']->balance = bcsub($systemAccount['system.gateway.external']->balance, $amount);
        $systemAccount['system.gateway.external.universe']->balance = bcsub($systemAccount['system.gateway.external.universe']->balance, $amount);
        $internalResult = ($systemAccount['system.gateway.external']->save() && $systemAccount['system.gateway.external.universe']->save());
        
        return $internalResult;
    }
    
    public static function transferFromExternal($currency, $userId, $amount) {
        $currencyName = mb_strtolower($currency);
        
        $externalGateway = GatewayFactory::create($currencyName);
        $externalResult = $externalGateway->transferFrom($address, $amount);
        
        if(!$externalResult) {
            return false;
        }
        
        $systemAccount = self::getSystemAccount($currency);
        $systemAccount['system.gateway.external']->balance = bcadd($systemAccount['system.gateway.external']->balance, $amount);
        $systemAccount['system.gateway.external.universe']->balance = bcadd($systemAccount['system.gateway.external.universe']->balance, $amount);
        $internalResult = ($systemAccount['system.gateway.external']->save() && $systemAccount['system.gateway.external.universe']->save());
        
        return $internalResult;
    }
    
    public static function getUserWallets($userId) {
        
        $accounts = Account::model()->findAllByAttributes(array('userId'=>$userId));
        
        $data = array();
        foreach($accounts as $value) {
            $data[$value->id] = $value;
        }
        
        return $data;
    }
    
    public static function checkHot($hotId) {   
        $hotWallet = self::model()->findByPk($hotId);
        $coldWallet = self::model()->findByAttributes(array(
            'type' => 'system.gateway.cold'
        ));
        
        if(!$hotWallet || !$coldWallet) {
            return false;
        }
        
        //system parameter
        $normalHotBalance = bcmul($coldWalelt->balance, 0.1);
        if(bccomp($normalHotBalance, $hotWalelt->balance)) {
            return false;
        }
        
        return true;
    }
    
    public static function getHot($currency, $gateway = false) {
        
        $conditions = array(
            'type' => 'system.gateway.hot',
            'currency' => $currency,
        );
        
        if($gateway) {
            $conditions['gateway'] = $gateway;
        }        
        
        return Account::model()->findAllByAttributes($conditions);
    }

    public static function getBestHotWallet($currency) {
        $sql = 'SELECT * FROM "account" where "balance"=(SELECT MIN("balance") FROM "account" WHERE "type"=\'system.gateway.hot\') AND "type"=\'system.gateway.hot\'';
        return Account::model()->findBySql($sql);
    }
    
    public static function createHot($currency) {
        
        $count = self::model()->countByAttributes(array(
            'type' => 'system.gateway.hot',
            'currency' => $currency
        ));
        
        $newAccountName = 'hw'.$count.rand(1, 100);
        
        try {
            BtcGateway::callBtcd('createHot', ['name'=>$newAccountName]);

            $wallet = new Account();
            $wallet->type = 'system.gateway.hot';
            $wallet->currency = $currency;
            $wallet->status = 'opened';
            $wallet->gateway = $newAccountName;
            $wallet->guid = Guid::generate();
            $wallet->createdAt = TIME;
            
        } catch(Exception $e) {
            return false;
        }
        
        if(!$wallet->save()) {            
            Loger::errorLog($wallet->getErrors());
            return false;
        }
        
        return true;
    }
    
    public static function getSupportedCurrency() {
        $pairs = self::getSupportedPairs();
        $currencies = array('main'=>array(), 'derived'=>array());

        foreach($pairs as $pair) {
            $onePair = explode('_', $pair);
            if(!in_array($onePair[1], $currencies['main'])) {
                $currencies['main'][] = $onePair[1];
            }
            $currencies['derived'][] = $onePair[0];
        }
        
        return $currencies;
    }
    
    
    public static function getSupportedPairs() {
        
        try {
            $connector = new TcpRemoteClient();
            $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_CURRENCY_PAIRS));
            $pairs = array();
            foreach($resultCore[1] as $value) {
                $pairs[] = mb_strtoupper($value);
            }
            return $pairs;
        } catch(Exception $e) {
            if($e instanceof TcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->getType());
            }
        }
        return false;
    }
    
}