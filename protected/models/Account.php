<?php

class Account extends CActiveRecord {

    private static $systemUserId = 0;
    
    public static $currencyOptions = ['BTC', 'USD', 'EUR'];
    
    public static $systemTypeOptions = [
        'system.gateway.external.universe', // отдано наружу / принято снаружи (сумма)
        'system.gateway.external.universe.unknown', // неизвестные платежи
        'system.gateway.external', // всегда плюс, реальный внешний кошелек/банк
        'system.gateway.internal', // оборот по шлюзу (при внешнем поступлении минус)
        'system.gateway.cold',
        'system.gateway.hot',
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

    public static function getMany(array $ids) {
        return $ids ? self::model()->findAllByPk($ids) : [];
    }
    
    public static function getList(array $pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = new CDbCriteria();
        if ($limit) {
            $pagination['total'] = (int)self::model()->count($criteria);
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
            $account->gatewayId = (int)$account->gatewayId;
            if ($account->gatewayId == 0) {
                $account->addError('gatewayId', 'Gateway not found');
            }
            //для пополнения счета шлюза
            if ($account->type == 'system.gateway.external') {

            }
        } //system account
        elseif (strpos($account->type, 'system.ticker.') === 0) {
            $account->userId = null;
            $account->gatewayId = null;
            if (!is_numeric($account->tickerId)) {
                $account->addError('tickerId', 'Ticker not found');
            }
        } // users account
        else {
            $account->tickerId = null;
            $account->gatewayId = null;
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
        
        $account = new Account();
        $account->currency = $currency;
        $account->status = 'opened';
        $account->userId = self::$systemUserId;
        $account->createdAt = TIME;
        
        $accountList = array();
        
        foreach(self::$systemTypeOptions as $wallet) {
            $account->setIsNewRecord(true);
            unset($account->id);
            $account->type = $wallet;
            $account->guid = Guid::generate();
            $account->save();
            array_push($accountList, $account);
        }
        return $accountList;
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
        
        $groupId = Guid::generate();

        $accountFrom = ($type)?$wallets['user.trading']:$wallets['user.safeWallet'];
        $accountTo = ($type)?$wallets['user.safeWallet']:$wallets['user.trading'];
        
        $transaction = new Transaction();
        $transaction->accountId = $accountFrom->id;
        $transaction->debit = 0;
        $transaction->credit = $amount;
        $transaction->createdAt = TIME;
        $transaction->groupId = $groupId;
        $transaction->currency = $currency;
        if (!$transaction->save()) {
            throw new SystemException(_('Something wrong with transaction creating'), $transaction->getErrors());
        }

        $transaction = new Transaction();
        $transaction->accountId = $accountTo->id;
        $transaction->debit = $amount;
        $transaction->credit = 0;
        $transaction->createdAt = TIME;
        $transaction->groupId = $groupId;
        $transaction->currency = $currency;
        if (!$transaction->save()) {
            throw new SystemException(_('Something wrong with transaction creating'), $transaction->getErrors());
        }
    }
    
    //type 0 = s to t, type 1 = t to s
    public static function transferToSafe($currency, $amount) {
        $user = Yii::app()->user;
        $wallets = self::getAccountPair($user->id, $currency);
        
        $compare = bccomp($wallets['user.trading']->balance, $amount);
        if($compare<0) {
            throw new ExceptionNoMoney();
        }
        
        //TODO: add commision
        $wallets['user.trading']->balance = bcsub($wallets['user.trading']->balance, $amount);
        $wallets['user.safeWallet']->balance = bcadd($wallets['user.safeWallet']->balance, $amount);
        
        $systemsAccounts = self::getSystemAccount($currency);
        $systemsAccounts['system.gateway.internal']->balance = bcsub($systemsAccounts['system.gateway.internal']->balance, $amount);
        
        if(in_array($currency, array('USD', 'BTC'))) {
            $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_REPLENISH_SAFE_ACCOUNT, $user->id, ($currency == 'USD')?1:0, $amount));
            if($resultCore != array()) {
                $result = false;
            }
        }
        
        self::createTransaction($wallets, $amount, 1, $currency);
        
        if(!$wallets['user.safeWallet']->save() || !$wallets['user.trading']->save() || !$systemsAccounts['system.gateway.internal']->save()) {
            throw new ExceptionAccount(); 
        }
        
        return true;
    }
    
    public static function transferToTrade($currency, $amount) {
        $user = Yii::app()->user;
        $wallets = self::getAccountPair($user->id, $currency);
        
        $compare = bccomp($wallets['user.safeWallet']->balance, $amount);
        if($compare<0) {
            throw new ExceptionNoMoney();
        }
        
        $wallets['user.safeWallet']->balance = bcsub($wallets['user.safeWallet']->balance, $amount);
        $wallets['user.trading']->balance = bcadd($wallets['user.trading']->balance, $amount);
        
        $systemsAccounts = self::getSystemAccount($currency);
        $systemsAccounts['system.gateway.internal']->balance = bcadd($systemsAccounts['system.gateway.internal']->balance, $amount);
        
        if(in_array($currency, array('USD', 'BTC'))) {
            $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            $result = $connector->sendRequest(array(TcpRemoteClient::FUNC_REPLENISH_TRADE_ACCOUNT, $user->id, ($currency == 'USD')?1:0, $amount));
            if($result != array()) {
                return false;
            }
        }
        
        self::createTransaction($wallets, $amount, 0, $currency);
        
        if(!$wallets['user.safeWallet']->save() || !$wallets['user.trading']->save() || !$systemsAccounts['system.gateway.internal']->save()) {
            throw new ExceptionAccount(); 
        }
        
        return false;
    }
    
    public static function getAccountInfo() {
        $user = Yii::app()->user;
        if(!$user) {
            return false;
        }
        
        $pair = Yii::app()->request->getParam('pair', 'BTC,USD');
        $accountList = Account::model()->findAllByAttributes(array(
            'userId'=>$user->id,
            'type'=> array('user.safeWallet'),
            ));
        
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_ACCOUNT_INFO, $user->id));

        $remoteAccountInfo = array(
            'firstAvailable' => $resultCore[0],
            'firstBlocked' => $resultCore[1],
            'secondAvailable' => $resultCore[2],
            'secondBlocked' => $resultCore[3],
            'comission' => $resultCore[4],
            'unknow' => $resultCore[5],
            'marginCall' => $resultCore[6],
        );

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

        return $data;
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
    
}