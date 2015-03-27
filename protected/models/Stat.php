<?php

class Stat extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'stat';
    }

    public function rules() {
        return [
                //['timestamp', 'numerical', 'allowEmpty' => false, 'min' => 1, 'max' => PHP_INT_MAX, 'integerOnly' => true],
                //['value, indicator', 'length', 'allowEmpty' => false, 'min' => 1, 'max' => 255],
                //['indicator', 'in', 'allowEmpty' => false, 'range' => self::$indicatorOptions, 'strict' => true],
        ];
    }
    
    public static function getFullStatByUser(array $data, array $filters) {
        
        $userId = ArrayHelper::getFromArray($data, 'userId');
        $currency = ArrayHelper::getFromArray($data, 'currency', false);

        $accountCriteria = array(
            'userId' => $userId,
            'type' => 'user.safeWallet'
        );
        
        if($currency != false) {
            $accountCriteria['currency'] = $currency;
        }
        
        
        $userAccountList = Account::model()->findAllByAttributes($accountCriteria);
        
        $data = array();
        
        foreach($userAccountList as $account) {
            $filters['common']['accountId'] = $account->id;
            $externalTransactions = TransactionExternal::getList($filters['common'], $filters['pagination']);
            
            $externalData = array(
                'income' => array(
                    'amount' => 0,
                    'count' => 0
                ),
                'outcome' => array(
                    'amount' => 0,
                    'count' => 0
                ),
            );
            
            foreach($externalTransactions as $transaction) {
                $type = ($transaction->type)? 'income':'outcome';

                $externalData[$type]['count']++;
                $externalData[$type]['amount'] = bcadd($externalData[$type]['amount'], $transaction->amount);
            }
            
            $data['external'][$account->currency] = $externalData;
        }
        
        return $data;
    }
    
    public static function getStatByFiat(array $data, array $filters) {
        
        $userId = ArrayHelper::getFromArray($data, 'userId');
        $address = ArrayHelper::getFromArray($data, 'address');
        
        $addressEntity = AddressExternal::model()->findByAttributes([
            'userId' => $userId,
            'address' => $address,
            ]);
        
        if(!$addressEntity) {
            return false;
        }
        
        $filters['common']['accountId'] = $addressEntity->id;
        $list = TransactionExternal::getList($filters['common'], $filters['pagination']);
        
        return $list;
    }
    
    public static function getStatByGateway($currency, $filters) {
        
        if(!$currency) {
            throw new Exception('Currency non set');
        }
        
        $externalTransactions = TransactionExternal::getList($filters['common'], $filters['pagination']);
        
        $data = array(
            'income' => array(
                'amount' => 0,
                'count' => 0
            ),
            'outcome' => array(
                'amount' => 0,
                'count' => 0
            ),
        );
        
        foreach($externalTransactions as $value) {
            $type = ($value->type)? 'income':'outcome';
            
            $data[$type]['count']++;
            $data[$type]['amount'] = bcadd($data[$type]['amount'], $value->amount);
        }
        
        
        return $data;
    }
    
    public static function mainStat($currency) {
        
        $stat = array(
            'dayVolume' => null,
            'bid' => null,
            'ask' => null,
            'todayOpen' => null,
            'dailyChangeCurr' => null,
            'dailyChangePercent' => null,
        );
        
        $todayRange = array(
            'open' => Response::timestampToTick(TIME - 86400),
            'close' => Response::timestampToTick(TIME)
        );
        
        $defaultPair = 'deal';
        
        //24 hour volume
        $dayVolumeQuery = 'SELECT SUM("size") as "size" FROM "deal" ';
        $dayVolumeQuery .= 'WHERE "createdAt" BETWEEN :open AND :close';
        $dayVolumeResult = Deal::model()->findBySql($dayVolumeQuery, array(
            ':open' => $todayRange['open'],
            ':close' => $todayRange['close'],
            ));
        if(isset($dayVolumeResult->size)) {
            $stat['dayVolume'] = Response::bcScaleOut($dayVolumeResult->size, 4);
        }
        
        try {
            $connection = new TcpRemoteClient();
            $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_GET_TICKER, mb_strtolower($currency)));
            
            if(!isset($response[0]) || $response[0] != 0) {
                throw new ExceptionTcpRemoteClient($response[0]);
            }
        } catch(Exception $e) {
            if($e instanceof TcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->getType());
            }
        }
        
        $stat['bid'] = Response::bcScaleOut($response[1], 4);
        $stat['ask'] = Response::bcScaleOut($response[2], 4);
        
        //Todays open price
        $todayOpenQuery = 'SELECT "price" FROM "deal" WHERE "createdAt" > :open ORDER BY "createdAt" ASC LIMIT 1';
        $todayOpenResult = Deal::model()->findBySql($todayOpenQuery, array(':open'=>$todayRange['open']));
        if(isset($todayOpenResult->price)) {
            $stat['todayOpen'] = Response::bcScaleOut($todayOpenResult->price, 4);
        }
        
        //daily change
        if(isset($stat['bid']) && isset($stat['todayOpen'])) {
            $cent = bcdiv('100', $stat['todayOpen'], 4);
            $stat['dailyChangeCurr'] = bcsub($stat['bid'], $stat['todayOpen'], 4);
            $stat['dailyChangePercent'] = bcmul($stat['dailyChangeCurr'], $cent, 4);
        }
        
        //high, low price by last 24 hours
        $priceRangeQuery = 'SELECT MAX("price") as "high", MIN("price") as "low" FROM "deal" WHERE "createdAt" BETWEEN :open AND :close';
        $priceRangeResult = Deal::model()->findBySql($priceRangeQuery, array(
            ':open' => $todayRange['open'],
            ':close' => $todayRange['close']
        ));
        
        if(isset($priceRangeResult)) {
            if(isset($priceRangeResult->high)) {
                $stat['high'] = $priceRangeResult->high;
            }
            if(isset($priceRangeResult->low)) {
                $stat['low'] = $priceRangeResult->low;
            }            
        }
        
        return $stat;
    }
    
    //normal stat
    
    public static function getCommisionIncomeByData($currency, $beginValue, $endValue, $userId = 0) {
        $begin = Response::timestampToTick($beginValue);
        $end = Response::timestampToTick($endValue);
        
        if($userId == 0) {
            $sellerQuery = 'SELECT SUM("sellerFee") as "sellerFee" FROM "deal" WHERE "side"=FALSE AND "currency"=:currency AND "createdAt" BETWEEN :begin AND :end';
            $sellerResultObject = Deal::model()->findBySql($sellerQuery, array(':currency'=>$currency, ':begin'=>$begin, ':end'=>$end));
            $buyerQuery = 'SELECT SUM("buyerFee") as "buyerFee" FROM "deal" WHERE "side"=FALSE AND "currency"=:currency AND "createdAt" BETWEEN :begin AND :end';
            $buyerResultObject = Deal::model()->findBySql($buyerQuery, array(':currency'=>$currency, ':begin'=>$begin, ':end'=>$end));
            $buyerOtherQuery = 'SELECT SUM("buyerFee") as "buyerFee" FROM "deal" WHERE "side"=TRUE AND "currency"=:currency AND "createdAt" BETWEEN :begin AND :end';
            $buyerOtherResultObject = Deal::model()->findBySql($buyerOtherQuery, array(':currency'=>$currency, ':begin'=>$begin, ':end'=>$end));
            $sellerOtherQuery = 'SELECT SUM("sellerFee") as "sellerFee" FROM "deal" WHERE "side"=TRUE AND "currency"=:currency AND "createdAt" BETWEEN :begin AND :end';
            $sellerOtherResultObject = Deal::model()->findBySql($sellerOtherQuery, array(':currency'=>$currency, ':begin'=>$begin, ':end'=>$end));
        } else {
            $sellerQuery = 'SELECT SUM("sellerFee") as "sellerFee" FROM "deal" WHERE ("userBuyId"=:userid OR "userSellId"=:userid) AND "side"=FALSE AND "currency"=:currency AND "createdAt" BETWEEN :begin AND :end';
            $sellerResultObject = Deal::model()->findBySql($sellerQuery, array(':currency'=>$currency, ':begin'=>$begin, ':end'=>$end, ':userid'=>$userId));
            $buyerQuery = 'SELECT SUM("buyerFee") as "buyerFee" FROM "deal" WHERE ("userBuyId"=:userid OR "userSellId"=:userid) AND "side"=FALSE AND "currency"=:currency AND "createdAt" BETWEEN :begin AND :end';
            $buyerResultObject = Deal::model()->findBySql($buyerQuery, array(':currency'=>$currency, ':begin'=>$begin, ':end'=>$end, ':userid'=>$userId));
            $buyerOtherQuery = 'SELECT SUM("buyerFee") as "buyerFee" FROM "deal" WHERE ("userBuyId"=:userid OR "userSellId"=:userid) AND "side"=TRUE AND "currency"=:currency AND "createdAt" BETWEEN :begin AND :end';
            $buyerOtherResultObject = Deal::model()->findBySql($buyerOtherQuery, array(':currency'=>$currency, ':begin'=>$begin, ':end'=>$end, ':userid'=>$userId));
            $sellerOtherQuery = 'SELECT SUM("sellerFee") as "sellerFee" FROM "deal" WHERE ("userBuyId"=:userid OR "userSellId"=:userid) AND "side"=TRUE AND "currency"=:currency AND "createdAt" BETWEEN :begin AND :end';
            $sellerOtherResultObject = Deal::model()->findBySql($sellerOtherQuery, array(':currency'=>$currency, ':begin'=>$begin, ':end'=>$end, ':userid'=>$userId));
        }
        
        $sellerResult = $sellerResultObject->sellerFee;
        $buyerResult = $buyerResultObject->buyerFee;
        $buyerOtherResult = $buyerOtherResultObject->buyerFee;
        $sellerOtherResult = $sellerOtherResultObject->sellerFee;
        
        return array(
            'comissionCurrency' => bcadd($buyerResult, $sellerOtherResult),
            'comissionEur' => bcadd($sellerResult, $buyerOtherResult),
        );
    }
    
    
    public static function getUsersStat($currency) {
        
        $balanceQuery = 'SELECT "type", SUM("balance") as "balance" FROM "account" WHERE "currency"=:currency AND ("type"=\'user.safeWallet\' OR "type"=\'user.trading\' OR "type"=\'user.withdrawWallet\') GROUP BY "type"';
        $balanceFetch = Account::model()->findAllBySql($balanceQuery, array(':currency'=>$currency));
        
        $data = array();
        foreach($balanceFetch as $value) {
            if($value->type == 'user.safeWallet') {
                $data['safe'] = $value->balance;
            } elseif($value->type == 'user.trading') {
                $data['trade'] = $value->balance;
            } else {
                $data['withdraw'] = $value->balance;
            }
        }
        
        //balance eur
        $balanceFetchEur = Account::model()->findAllBySql($balanceQuery, array(':currency'=>'EUR'));
        
        foreach($balanceFetchEur as $value) {
            if($value->type == 'user.safeWallet') {
                $data['safeEur'] = $value->balance;
            } elseif($value->type == 'user.trading') {
                $data['tradeEur'] = $value->balance;
            } else {
                $data['withdrawEur'] = $value->balance;
            }
        }
        
        
        //comission 
        $monthData = new DateTime('first day of this month');
        $dayData = new DateTime();
        $dayData->setTime(0, 0, 0);
        
        
        $data['allTime'] = self::getCommisionIncomeByData($currency, '0', TIME);
        $data['lastMonth'] = self::getCommisionIncomeByData($currency, $monthData->getTimestamp(), TIME);
        $data['lastDay'] = self::getCommisionIncomeByData($currency, $dayData->getTimestamp(), TIME);
        
        
        
        //currency hot, cold
        $currencyColdQuery = 'SELECT SUM("balance") as "balance" FROM "account" WHERE "currency"=:currency AND "type"=\'system.gateway.cold\'';
        $currencyCold = Account::model()->findBySql($currencyColdQuery, array(':currency'=>$currency));
        
        if(!$currencyCold || $currencyCold->balance == null) {
            $data['coldCurrency'] = 0;
        } else {
            $data['coldCurrency'] = $currencyCold->balance;
        }
        
        if($currency == 'BTC') {
            $hotGateway = GatewayFactory::create(2);
            $hotBalance = $hotGateway->callForMoney();
            if($hotBalance == false) {
                $data['hotCurrency'] = -1;
            } else {
                $data['hotCurrency'] = $hotBalance['balance'];
            }
            
        } else {
            $data['hotCurrency'] = 0;
        }
        
        $data['externalAmountCurrency'] = bcadd($data['hotCurrency'], $data['coldCurrency']);
        
        $eurExternalQuery = 'SELECT SUM("balance") as "balance" FROM "account" WHERE "currency"=\'EUR\' AND "type"=\'system.gateway.cold\'';
        $eurExternal = Account::model()->findBySql($eurExternalQuery);
        if(!$eurExternal) {
            $data['externalAmountEur'] = 0;
        } else {
            $data['externalAmountEur'] = $eurExternal->balance;
        }
        
        //active orders
        $activeOrdersQuery = 'SELECT SUM("actualSize") as "actualSize" FROM "order" WHERE "currency"=:currency AND "side"=TRUE AND ("status"=\'accepted\' OR "status"=\'partialFilled\')';
        $activeOrder = Order::model()->findBySql($activeOrdersQuery, array(':currency'=>$currency));

        if(!$activeOrder || !isset($activeOrder->actualSize)) {
            $data['activeOrderCurrency'] = 0;
        } else {
            $data['activeOrderCurrency'] = $activeOrder->actualSize;
        }
        
        $data['internalAmountCurrency'] = bcadd(bcadd(bcadd($data['safe'], $data['trade']), $data['withdraw']), $data['activeOrderCurrency']);
        
        //active orders eur
        $activeOrdersEurQuery = 'SELECT SUM("actualSize") as "actualSize" FROM "order" WHERE "currency"=:currency AND "side"=FALSE AND ("status"=\'accepted\' OR "status"=\'partialFilled\')';
        $activeOrderEur = Order::model()->findBySql($activeOrdersEurQuery, array(':currency'=>'EUR'));

        if(!$activeOrderEur || !isset($activeOrderEur->actualSize)) {
            $data['activeOrderEur'] = 0;
        } else {
            $data['activeOrderEur'] = $activeOrder->actualSize;
        }
        
        $data['internalAmountEur'] = bcadd(bcadd(bcadd($data['safeEur'], $data['tradeEur']), $data['withdrawEur']), $data['activeOrderEur']);
        
        //earning
        $data['earningCurrency'] = bcsub($data['externalAmountCurrency'], $data['internalAmountCurrency']);
        $data['earningEur'] = bcsub($data['externalAmountEur'], $data['internalAmountEur']);
        
        
        //internal, external
        $internalCurrency = Account::model()->findByAttributes(array(
            'type' => 'system.gateway.internal',
            'currency' => $currency
        ));        
        $internalEur = Account::model()->findByAttributes(array(
            'type' => 'system.gateway.internal',
            'currency' => 'EUR'
        ));
        
        
        $externalCurrency = Account::model()->findByAttributes(array(
            'type' => 'system.gateway.external',
            'currency' => $currency
        ));
        $externalEur = Account::model()->findByAttributes(array(
            'type' => 'system.gateway.external',
            'currency' => 'EUR'
        ));
        
        $data['gatewayCurrency'] = bcsub($externalCurrency->balance, $internalCurrency->balance);
        $data['gatewayEur'] = bcsub($externalEur->balance, $internalEur->balance); 
        
        //total spread
        $connection = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $response = $connection->sendRequest(array(TcpRemoteClient::FUNC_GET_DEPTH, mb_strtolower($currency), 30));

        if(!isset($response[0]) || $response[0] != 0) {
            throw new ExceptionTcpRemoteClient($response[0]);
        }
        
        $data['depth'] = array(
            'volumeEur' => $response[1],
            'volumeCurrency' => $response[2],
            'countBuy' => $response[3],
            'countSell' => $response[4],
        );
        
        if(count($response[5]) > 0 && count($response[6]) > 0) {
            $lastBuy = $response[5][0];
            $lastSell = $response[6][0];
            
            $data['spread'] = bcsub($lastSell[1], $lastBuy[1]);
        } else {
            $data['spread'] = 0;
        }
        
        return $data;
    }
 
    public static function personalStat($userId, $currency) {
        
        $data = array();
        
        //accounts
        $accounts = Account::model()->findAllByAttributes(array(
            'currency' => $currency,
            'type' => array('user.safeWallet', 'user.trading'),
            'userId' => $userId
        ));
        
        if($accounts) {
            $data['accounts'] = $accounts;
        }
        
        //comissions
        $monthData = new DateTime('first day of this month');
        $dayData = new DateTime();
        $dayData->setTime(0, 0, 0);
        
        $data['allTime'] = self::getCommisionIncomeByData($currency, '0', TIME, $userId);
        $data['lastMonth'] = self::getCommisionIncomeByData($currency, $monthData->getTimestamp(), TIME, $userId);
        $data['lastDay'] = self::getCommisionIncomeByData($currency, $dayData->getTimestamp(), TIME, $userId);

        
        
        
        return $data;
    }
    
}