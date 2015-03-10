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
        $dayVolumeQuery = 'SELECT SUM("size") as "size" FROM "'.$defaultPair.'" ';
        $dayVolumeQuery .= 'WHERE "createdAt" BETWEEN '.$todayRange['open'];
        $dayVolumeQuery .= ' AND '.$todayRange['close'];
        $dayVolumeResult = Deal::model()->findBySql($dayVolumeQuery);
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
        $todayOpenQuery = 'SELECT "price" FROM "deal" WHERE "createdAt" > '.$todayRange['open'].' ORDER BY "createdAt" ASC LIMIT 1';
        $todayOpenResult = Deal::model()->findBySql($todayOpenQuery);
        if(isset($todayOpenResult->price)) {
            $stat['todayOpen'] = Response::bcScaleOut($todayOpenResult->price, 4);
        }
        
        //daily change
        if(isset($stat['bid']) && isset($stat['todayOpen'])) {
            $cent = bcdiv('100', $stat['todayOpen'], 4);
            $stat['dailyChangeCurr'] = bcsub($stat['bid'], $stat['todayOpen'], 4);
            $stat['dailyChangePercent'] = bcmul($stat['dailyChangeCurr'], $cent, 4);
        }
        
        return $stat;
    }
    
}