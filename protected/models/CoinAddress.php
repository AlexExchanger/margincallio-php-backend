<?php

class CoinAddress extends CActiveRecord
{
    static $systemOptions = ['bitcoin'];

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'coin_address';
    }

    public function rules() {
        return [
            ['address', 'length', 'allowEmpty' => false, 'min' => 27, 'max' => 34],
            ['accountId', 'numerical', 'allowEmpty' => false, 'min' => 1, 'integerOnly' => true],
        ];
    }
    
    public static function getByAddress($address) {
        return self::model()->findByAttributes(array('address'=>$address));
    }
    
    public static function generateNewAwating() {
        $request = array(
            'request' => json_encode(array('action' => 'addToWaitingTransactions')),
        );
        
        $request['sign'] = md5($request['request'].'salt');
        $bitcoinService = curl_init();
        curl_setopt_array($bitcoinService, array(
            CURLOPT_URL => Yii::app()->params->bitcoinService['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request,
        ));
        
        $response = curl_exec($bitcoinService);
        curl_close($bitcoinService);
        
        if(!$response) {
            return false;
        }
        
        $data = json_decode($response, true);
        
        if(!$data['success']) {
            return false;
        }
        
        return $data['data'][0];
    }
    
    public static function getNewAddress() {
        
        $request = array(
            'request[action]' => 'getNewAddress',
            'time' => TIME
        );
        
        $request['sign'] = md5($request['request[action]'].TIME.'salt');
        $bitcoinService = curl_init();
        curl_setopt_array($bitcoinService, array(
            CURLOPT_URL => Yii::app()->params->bitcoinService['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request,
        ));
        
        $response = curl_exec($bitcoinService);
        curl_close($bitcoinService);
        
        if(!$response) {
            return false;
        }
        
        $data = json_decode($response, true);
        
        if(!$data['success']) {
            return false;
        }
        
        return $data['data'][0];
    }
}
