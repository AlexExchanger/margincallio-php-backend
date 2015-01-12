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
    
    //external stat methods
    /*
     *  $data = [
     *      'userId' => User ID (required)
     *      'currency' => 
     *      'status' => Range of statuses for searched transactions
     *      'address' => Address (required or ID)
     *  ]
     */
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
        
        $gatewaysAll = Account::getSystemAccount($currency);
        
        $internalTransactions = Transaction::getList($filters['common'], $filters['pagination']);
        $externalTransactions = TransactionExternal::getList($filters['common'], $filters['pagination']);
        
//        $gateways = array();
//        foreach($gatewaysAll as $value) {
//            $gateways[] = array(
//                'type' => $value->type,
//                'guid' => $value->guid,
//                'balance' => $value->balance
//            );
//        }
//        
//        $internal = array(
//            'credit' => 0,
//            'debit' => 0,
//            'count' => count($internalTransactions)
//        );
//        $external = array(
//            'credit' => 0,
//            'debit' => 0,
//            'count' => count($internalTransactions),
//        );
//        
//        foreach($internalTransactions as $value) {
//            $internal['credit'] += $value->credit;
//            $internal['debit'] += $value->debit;
//        }
//        
//        foreach($externalTransactions as $value) {
//            $external['credit'] += $value->credit;
//            $external['debit'] += $value->debit;
//        }
//        
//        return array(
//            'gateways' => $gateways,
//            'internal' => $internal,
//            'external' => $external,
//        );
        
        return array();
    }
}