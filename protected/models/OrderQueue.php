<?php


class OrderQueue extends CActiveRecord
{

    public function tableName()
    {
        return 'order_queue';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return array();
    }

    public function beforeSave()
    {
        $this->data = json_encode($this->data, JSON_UNESCAPED_UNICODE);
        return parent::beforeSave();
    }

    public function afterSave()
    {
        parent::afterSave();
        $this->data = json_decode($this->data, true);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->data = json_decode($this->data, true);
    }


    public static function addOperation($orderGuid, $operationId, array $operation)
    {
        $order = new self();
        $order->orderGuid = $orderGuid;
        $order->operationId = $operationId;
        $order->data = $operation;
        $order->createdAt = TIME;
        if (!$order->save()) {
            throw new ModelException($order->getErrors());
        }
        return $order;
    }


    public static function getOperation($orderGuid, $operationId)
    {
        $operation = self::model()->findByAttributes([
            'orderGuid' => $orderGuid,
            'operationId' => $operationId
        ]);
        if (!$operation) {
            return null;
        }
        return $operation->data;
    }
}