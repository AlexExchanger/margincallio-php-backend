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
}