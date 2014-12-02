<?php

class Ticket extends CActiveRecord
{
    public $user;

    public static $statusOptions = ['waitForUser', 'waitForSupport', 'closed'];
    public static $departmentOptions = ['general', 'finance', 'verification', 'security', 'partners'];

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'ticket';
    }

    public function rules() {
        return array(
            array('status', 'in', 'allowEmpty' => false, 'range' => self::$statusOptions, 'strict' => true),
            array('department', 'in', 'allowEmpty' => false, 'range' => self::$departmentOptions, 'strict' => true),
            array('title', 'length', 'allowEmpty' => false, 'min' => 1, 'max' => 255),
        );
    }
    
    public function relations() {
        return array(
            'messages' => array(self::HAS_MANY, 'TicketMessage', 'ticketId')
        );
    }
    

    public static function get($id) {
        return Ticket::model()->findByPk($id);
    }
    
    public static function getByUser($id, $userId) {        
        return Ticket::model()->findByAttributes(array('id'=>$id, 'createdBy'=>$userId));
    }

    public static function create(array $data, $text, $userId, File $file = null) {
        
        $ticket = new Ticket();
        $ticket->title = ArrayHelper::getFromArray($data, 'title');
        $ticket->department = ArrayHelper::getFromArray($data, 'department');
        $ticket->createdAt = TIME;
        $ticket->updatedAt = null;
        $ticket->createdBy = $userId;
        $ticket->updatedBy = $userId;
        $ticket->messageCount = 1;
        $ticket->status = 'waitForSupport';

        if (!$ticket->validate()) {
            throw new ModelException('Ticket not save', $ticket->getErrors());
        }
        try {
            if ($ticket->save()) {
                TicketMessage::create($ticket, array('text' => $text), $userId, $file);
                return $ticket;
            } else {
                throw new ModelException('Ticket not save', $ticket->getErrors());
            }
        }
        catch (ModelException $e) {
            throw $e;
        }
        catch (Exception $e) {
            print_r($e->getMessage()); die();
        }
    }


    public static function modify(Ticket $ticket, array $data, $text, $userId, $file = null) {
        $status = ArrayHelper::getFromArray($data, 'status');
        $update = [];

        $transaction = $ticket->dbConnection->beginTransaction();

        try {
            //обновляем статус
            if (!empty($status) && $ticket->status !== $status) {
                //статус тикета изменен
                $update['status'] = $status;
                $ticket->status = $status;
            }

            //сохраняем сообщение
            if (!empty($text)) {
                $ticketMessage = TicketMessage::create($ticket, ['text' => $text], $userId, $file);
                $ticket->messageCount = TicketMessage::getCountByTicket($ticket);
                $update['messageCount'] = $ticket->messageCount;

                if ($userId != $ticket->createdBy) {
                    //меняем статус на waitForUser
                    $update['status'] = 'waitForUser';
                    $ticket->status = 'waitForUser';
                } else {
                    $update['status'] = 'waitForSupport';
                    $ticket->status = 'waitForSupport';
                }
            }

            //надо обновлять
            if ($update) {
                $ticket->updatedAt = TIME;
                $ticket->updatedBy = $userId;

                if ($ticket->save(true, ['status', 'updatedAt', 'updatedBy', 'messageCount'])) {
                    //TODO послать мыло если чё
                } else {
                    throw new ModelException('Ticket did not updated', $ticket->getErrors());
                }
            }

            $transaction->commit();
            return $ticket;
        }
        catch (ModelException $e) {
            $transaction->rollback();
            throw $e;
        }
        catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }


    private static function getListCriteria(array $filters) {
        $query = ArrayHelper::getFromArray($filters, 'query');
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');
        $status = ArrayHelper::getFromArray($filters, 'status');
        $userId = ArrayHelper::getFromArray($filters, 'userId');
        $email = ArrayHelper::getFromArray($filters, 'email');
        $department = ArrayHelper::getFromArray($filters, 'department');

        $criteria = new CDbCriteria();
        if (!empty($query)) { //ищем по title
            $criteria->addSearchCondition('title', $query);
        }

        if (!empty($userId)) { //ищем по userId или по userPublicId
            $user = User::get($userId);
            $uid = ($user) ? $user->id : 0;
            $criteria->compare('createdBy', $uid);
        }

        if (!empty($email)) { //ищем по мылу юзера
            $user = User::getByEmail($email);
            $userId = ($user) ? $user->id : 0;
            $criteria->compare('createdBy', $userId);
        }

        if (!empty($status) && in_array($status, Ticket::$statusOptions)) { //ищем по статусу
            $criteria->compare('status', $status);
        }

        if (!empty($department)) {
            $criteria->compare('department', $department);
        }

        ListCriteria::dateCriteria($criteria, $dateFrom, $dateTo);

        return $criteria;
    }


    public static function getList(array $filters, array &$pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = self::getListCriteria($filters);
        if ($limit) {
            $pagination['total'] = (int)self::model()->count($criteria);
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return self::model()->findAll($criteria);
    }
    
    public static function getMessageList($ticketId, array &$pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        if(!$ticketId) {
            throw new ExceptionWrongTicketId();
        }
        
        $criteria = new CDbCriteria();
        $criteria->compare('ticketId', $ticketId);

        if ($limit) {
            $pagination['total'] = TicketMessage::model()->count($criteria);
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return TicketMessage::model()->findAll($criteria);
    }


    public static function getStatusStat(array $filters = []) {
        $criteria = self::getListCriteria($filters);
        $statuses = [];
        foreach (Ticket::$statusOptions as $tstatus) {
            $statuses[$tstatus] = 0;
        }
        $criteria->select = 'COUNT(*) as c, status';
        $criteria->group = 'status';
        $stats = Ticket::model()->dbConnection->commandBuilder->createFindCommand('ticket', $criteria)->queryAll();
        foreach ($stats as $stat) {
            $statuses[$stat['status']] = (int)$stat['c'];
        }
        return $statuses;
    }
}