<?php

class GatewayFactory extends CComponent {
    
    protected static function getClassById($id) {
        
        $gateway = Yii::app()->db->createCommand()
                ->select('class')
                ->from('gateway gg')
                ->where('id=:id', array(':id'=>$id))
                ->queryRow(true);
        
        if(!isset($gateway['class'])) {
            return null;
        }
        
        return $gateway['class'];
    }
    
    public static function create($id) {
        if (!isset($id)) {
            return false;
        }
        
        $className = self::getClassById($id);
        if (class_exists($className)) {
            return $className::getInstance();
        }
        return false;
    }
}