<?php

class Funds extends CModel {

    protected static function checkPair($pair) {
        try {
            $currencies = explode('/', $pair);
            foreach($currencies as $value) {
                if(!in_array($value, Yii::app()->params['supportedCurrency'])) {
                    throw new Exception();
                }
            }
        } catch(Exception $e) {
            return false;
        }
        return true;
    }

    public function attributeNames() {
        return array(
            'id' => 'Id',
            'currency' => 'Currency',
        );
    }
    
    public static function convertFunds($value, $pair, $type) {
        if(!self::checkPair($pair)) {
            return false;
        }
        
        $rateParam = System::getSystemParams('rate'.implode('', explode('/', $pair)));
        if(!$rateParam || !isset($rateParam[0])) {
            return false;
        }
        
        $rate = array_pop($rateParam);
        try {
            $result = ($type)? bcdiv($value, $rate->value):bcmul($value, $rate->value);
        } catch(Exception $e) {
            return false;
        }

        return $result;
    }
    
    public static function addPairRate($pair, $value) {
        if(!self::checkPair($pair)) {
            return false;
        }
        
        $pairName = 'rate'.implode('', explode('/', $pair));
        
        $rate = System::getSystemParams($pairName);
        if($rate) {
            return false;
        }
        
        $system = new System();
        $system->name = $pairName;
        $system->value = $value;
        
        return $system->save();
    }
    
    public static function updatePairRate($id, $value) {
        $rateParam = System::getSystemParamsById($id);
        if(!$rateParam || !isset($rateParam[0])) {
            return false;
        }
        
        $rate = array_pop($rateParam);
        $rate->value = $value;
        
        return $rate->save(true, array('value'));
    }
}
