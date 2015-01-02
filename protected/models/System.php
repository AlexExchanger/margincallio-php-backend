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
    
    public static function getSystemParams($name) {
        return self::model()->findAllByAttributes(array('name'=>$name));
    }
    
    public static function getSystemParamsById($id) {
        return self::model()->findAllByPk($id);
    }
    
}