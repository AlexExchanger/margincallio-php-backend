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
    
}