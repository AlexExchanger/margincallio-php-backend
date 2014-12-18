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

    public static function create(Ticket $ticket, array $data, $userId, $file = null) {
        $text = ArrayHelper::getFromArray($data, 'text', '');
        $ticketMessage = new TicketMessage();
        $ticketMessage->createdBy = $userId;
        $ticketMessage->createdAt = TIME;
        $ticketMessage->text = $text;
        $ticketMessage->ticketId = $ticket->id;
        try {
            if ($ticketMessage->save()) {
                if ($file) {
                    if(is_array($file)) {
                        foreach ($file as $item) {
                            $ticketMessage->assign($item->id);
                        }
                    } else {
                        $ticketMessage->assign($item->id);
                    }
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
    
    public function assign($fileId) {
        if(!$this->id) {
            return false;
        }
        
        $currentFiles = explode(',', $this->files);
        if(!in_array($fileId, $currentFiles)) {
            array_push($currentFiles, $fileId);
        }
        
        $this->files = implode(',', $currentFiles);
        return $this->save(true, array('files'));
    }
    
    
    public function unassign($fileId) {
        if(!$this->id) {
            return false;
        }
        
        $currentFiles = explode(',', $this->files);
        $position = array_search($fileId, $currentFiles);
        
        if(in_array($fileId, $currentFiles)) {
            unset($currentFiles[$position]);
        }
        
        $this->files = implode(',', $currentFiles);
        return $this->save(true, array('files'));
    }
    
    
}