<?php

class Transaction extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'transaction';
    }

    public function rules() {
        return [
            ['account_from, account_to, user_from, user_to', 'numerical', 'allowEmpty' => false, 'min' => 1, 'integerOnly' => true],
            ['amount', 'numerical', 'allowEmpty' => false, 'min' => 0, 'max' => 1000000, 'integerOnly' => false],
        ];
    }


    public function checkGuid($attribute, $params) {
        if (!Guid::validate($this->$attribute)) {
            $this->addError($this->$attribute, 'Guid invalid');
        }
    }

    public static function get($id) {
        return Transaction::model()->findByPk($id);
    }

    public static function create(array $data) {
        $model = new Transaction();
        
        $model->account_from = ArrayHelper::getFromArray($data, 'account_from');
        $model->account_to = ArrayHelper::getFromArray($data, 'account_to');
        $model->amount = ArrayHelper::getFromArray($data, 'amount');
        
        $user_from = ArrayHelper::getFromArray($data, 'user_from', false);
        $user_to = ArrayHelper::getFromArray($data, 'user_to', false);
        
        $model->user_from = (!$user_from)?Account::getUserByAccount($model->account_from):$user_from;
        $model->user_to = (!$user_to)?Account::getUserByAccount($model->account_to):$user_to;
        
        $model->createdAt = TIME;

        try {
            if ($model->save()) {
                return $model;
            } else {
                throw new ModelException($model->getErrors());
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function getList(array $filters, array &$pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = self::getListCriteria($filters);
        $pagination['total'] = (int) self::model()->count($criteria);
        if ($limit) {
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return self::model()->findAll($criteria);
    }
    
    public static function getUsers(&$transaction) {
        
        $users = array();
        foreach($transaction as $value) {
            if(isset($value->user_from) && $value->user_from != null) {
                $users[$value->user_from] = $value->user_from;
            }
            
            if(isset($value->user_to) && $value->user_to != null) {
                $users[$value->user_to] = $value->user_to;
            }
        }
        
        $usersObj = User::model()->findAllByAttributes(array('id'=>array_values($users)));
        
        foreach($usersObj as $value) {
            $users[$value->id] = $value;
        }
        
        foreach($transaction as $key=>$value) {
            if(isset($value->user_from) && $value->user_from != null) {
                $transaction[$key]->user_from = array(
                    'id' => isset($users[$value->user_from])? $users[$value->user_from]->id:'',
                    'email' => isset($users[$value->user_from])? $users[$value->user_from]->email:'',
                );
            }

            if(isset($value->user_to) && $value->user_to != null) {
                $transaction[$key]->user_to = array(
                    'id' => isset($users[$value->user_to])? $users[$value->user_to]->id:'',
                    'email' => isset($users[$value->user_to])? $users[$value->user_to]->email:'',
                );
            }
        }
        
        return $usersObj;
    }
    

    public static function getStats(array $filters) {
        $result = [
            'all' => 0,
            'spend' => 0,
            'earn' => 0,
        ];
        $criteria = self::getListCriteria($filters);
        $result['spend'] = (int) self::model()->count($criteria);
        $filters['account_to'] = $filters['account_from'];
        unset($filters['account_from']);
        $result['earn'] = (int) self::model()->count($criteria);
        
        $result['all'] = $result['spend'] + $result['earn'];
        
        return $result;
    }

    private static function getListCriteria(array $filters) {
        $accountFrom = ArrayHelper::getFromArray($filters, 'account_from');
        $accountTo = ArrayHelper::getFromArray($filters, 'account_to');
        $accountOr = ArrayHelper::getFromArray($filters, 'account_or');
        
        $userFrom = ArrayHelper::getFromArray($filters, 'user_from');
        $userTo = ArrayHelper::getFromArray($filters, 'user_to');
        
        $currency = ArrayHelper::getFromArray($filters, 'currency');
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');

        $criteria = new CDbCriteria();
        
        if(!empty($accountOr) && $accountOr == true) {
            $criteria->addCondition('"account_from"='.$accountFrom.' OR "account_to"='.$accountTo);
        } else {
            if (!empty($accountFrom)) {
                $criteria->compare('account_from', $accountFrom);
            }
            if (!empty($accountTo)) {
                $criteria->compare('account_to', $accountTo);
            }
        }
        
        if (!empty($userFrom)) {
            $criteria->compare('user_from', $accountFrom);
        }
        if (!empty($userTo)) {
            $criteria->compare('user_to', $userTo);
        }
        
        if (!empty($currency) && in_array($currency, Yii::app()->params['supportedCurrency'])) {
            $criteria->compare('currency', $currency);
        }
        
        ListCriteria::timestampCriteria($criteria, $dateFrom, $dateTo);

        return $criteria;
    }

}
