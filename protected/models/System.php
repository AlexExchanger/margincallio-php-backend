<?php

class System extends CActiveRecord {

    public function attributeNames() {
        return array(
            'id' => 'Id',
            'name' => 'Name',
            'value' => 'Value'
        );
    }
    
    public function tableName() {
        return 'system';
    }

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
}
    
    