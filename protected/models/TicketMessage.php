<?php

class TicketMessage extends CActiveRecord {
    public $user;
    public $files;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'ticket_message';
    }

    public function rules() {
        return array(
            array('text', 'length', 'allowEmpty' => false, 'min' => 1, 'max' => 20000),
        );
    }

    public static function getByTicket(Ticket $ticket) {
        return TicketMessage::model()->findAllByAttributes(array('ticketId' => $ticket->id), array('order' => 'id DESC'));
    }

    public static function getCountByTicket(Ticket $ticket) {
        return (int)TicketMessage::model()->countByAttributes(array('ticketId' => $ticket->id));
    }

    public static function create(Ticket $ticket, array $data, $userId, File $file = null) {
        $text = ArrayHelper::getFromArray($data, 'text', '');
        $ticketMessage = new TicketMessage();
        $ticketMessage->createdBy = $userId;
        $ticketMessage->createdAt = TIME;
        $ticketMessage->text = $text;
        $ticketMessage->ticketId = $ticket->id;
        try {
            if ($ticketMessage->save()) {
                if ($file) {
                    //File::assign($ticketMessage, $file, $userId);
                }
                return $ticketMessage;
            } else {
                throw new ModelException('Ticket Message not create', $ticketMessage->getErrors());
            }
        }
        catch (ModelException $e) {
            throw $e;
        }
        catch (Exception $e) {
            throw $e;
        }
    }
}