<?php

class AddressExternal extends CActiveRecord
{
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'user_external_address';
    }

    public function rules() {
        return [
//            ['address', 'length', 'allowEmpty' => false, 'min' => 27, 'max' => 34],
//            ['accountId', 'numerical', 'allowEmpty' => false, 'min' => 1, 'integerOnly' => true],
        ];
    }
    
    
}
