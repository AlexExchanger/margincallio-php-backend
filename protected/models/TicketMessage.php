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

    public static function getLastMessages($ticketId, $count) {
        $messages = TicketMessage::model()->findAllByAttributes(array('ticketId' => $ticketId), array('order' => 'id DESC'));
        $allMessage = array();
        foreach ($messages as $value) {
            $currentFilesObjects = array();
            if(!is_null($value->files)) {
                $currentFiles = explode(',', $value->files);
                foreach($currentFiles as $oneFile) {
                    if(!isset($allFiles[$oneFile])) {
                        $file = File::model()->findByPk($oneFile);
                        if(!$file) {continue;}
                        $allFiles[$oneFile] = array(
                            'url' => '/files/'.$file->uid,
                            'uid' => $file->uid,
                            'type' => $file->entityType
                        );
                    }
                    $currentFilesObjects[] = $allFiles[$oneFile];
                }
            }
            
            $allMessage[] = array(
                'id' => $value->id,
                'createdBy' => ($value->createdBy == Yii::app()->user->id)? $value->createdBy: null,
                'createdAt' => $value->createdAt,
                'text' => $value->text,
                'files' => $currentFilesObjects
            );
        }
        
    }
    
    
    
    public static function create(Ticket $ticket, array $data, $userId, $file = null) {
        $text = ArrayHelper::getFromArray($data, 'text', '');
        $ticketMessage = new TicketMessage();
        $ticketMessage->createdBy = $userId;
        $ticketMessage->createdAt = TIME;
        $ticketMessage->text = $text;
        $ticketMessage->ticketId = $ticket->id;
        $ticketMessage->files = ArrayHelper::getFromArray($data, 'files');
        
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