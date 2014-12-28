<?php

class Funds extends CModel {

    public function attributeNames() {
        return array(
            'id' => 'Id',
            'currency' => 'Currency',
        );
    }
    
    public static function convertFunds() {
        //Get funds exchange rate
        
    }
    

}

