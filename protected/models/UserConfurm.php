<?php

class UserConfurm extends CActiveRecord { 
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'user_transaction_confurm';
    }
    
    public static function generateForUser($userId, $details) {
        
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {

            $confurm = new UserConfurm();
            $confurm->code = UserIdentity::trickyPasswordEncoding(md5($userId), md5(json_encode($details)));
            $confurm->userId = $userId;
            $confurm->details = json_encode($details);
            
            if(!$confurm->save()) {
                throw new Exception('Can\'t save confurm');
            }
            
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }
        
        return $confurm;
    }
    
}