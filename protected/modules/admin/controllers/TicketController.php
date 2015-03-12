<?php

class TicketController extends AdminController {
    
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);
        
        return true;
    }

    public function actionViewActiveTickets() {        
        $userId = $this->getParam('userId', false);
        $status = $this->getParam('status', 'waitForSupport');
        
        try {
            if(!in_array($status, Ticket::$statusOptions) || $status == 'closed') {
                throw new Exception();
            }
            
            $filters = array(
                'status' => $status,
                'userId' => ($userId != false)? $userId: null
            );
            
            $tickets = Ticket::getList($filters, $this->paginationOptions);
            
            $ticketsData = array(
                'count' => (isset($this->paginationOptions))?$this->paginationOptions['total']:'',
                'data' => $tickets,
            );
            
        } catch (Exception $e) {
            Response::ResponseError('Unknow error');
        }
        
        Response::ResponseSuccess($ticketsData);
    }
    
    public function actionViewTicket() {        
        $ticketId = $this->getParam('ticketId', false);
        
        try {
            $ticket = Ticket::model()->findByPk($ticketId);
            $tickets = array($ticket);
            $users = Ticket::getUsers($tickets);
            
            $messages = Ticket::getMessageList($ticketId, $this->paginationOptions, $users);
        } catch (Exception $e) {
            Response::ResponseError('Unknow error');
        }

        Response::ResponseSuccess(array(
            'ticket' =>  isset($tickets[0])? $tickets[0]:'',
            'messages' => $messages
        ));
    }
    
    private function view($type) {
        $userId = $this->getParam('userId', false);
        $status = $this->getParam('status', 'waitForSupport');
        
        try {
            $filters = array(
                'status' => $status,
                'userId' => ($userId != false) ? $userId : null,
                'department' => $type,
            );

            $tickets = Ticket::getList($filters, $this->paginationOptions);
            $users = Ticket::getUsers($tickets);
            
            $ticketsData = array(
                'count' => (isset($this->paginationOptions))?$this->paginationOptions['total']:'',
                'data' => $tickets,
            );
            
        } catch (Exception $e) {
            Response::ResponseError('Unknow error');
        }
        
        Response::ResponseSuccess($ticketsData);
    }
    
    public function actionViewGeneral() {
        $this->view('general');
    }
    
    public function actionViewFinance() {
        $this->view('finance');
    }
    
    public function actionViewVerification() {
        $this->view('verification');
    }
    
    public function actionViewSecurity() {
        $this->view('security');
    }
    
    public function actionViewPartners() {
        $this->view('partners');
    }
    
    public function actionAll() {
        $userId = $this->getParam('userId', false);
        $status = $this->getParam('status', false);
        
        try {
            $filters = array('userId' => ($userId != false) ? $userId : null);

            if($status != false) {
                $filters['status'] = $status;
            }
            
            $tickets = Ticket::getList($filters, $this->paginationOptions);
            Ticket::getUsers($tickets);
            
            $ticketsData = array(
                'count' => (isset($this->paginationOptions))?$this->paginationOptions['total']:'',
                'data' => $tickets,
            );
            
        } catch (Exception $e) {
            Response::ResponseError('Unknow error');
        }
        
        Response::ResponseSuccess($ticketsData);
    }
    
    public function actionReplyForTicket() {
        
        $ticketId = Yii::app()->request->getParam('ticketId');
        $text = $this->getParam('text', null);
        
        if (isset($_FILES) && count($_FILES) > 0) { 
            //files
            $files = array();
            try {
                foreach ($_FILES as $key => $value) {
                    $file = new File();
                    $file->fileName = $value['name'];
                    $file->fileSize = $value['size'];
                    $file->fileItem = new CUploadedFile($value['name'], $value['tmp_name'], $value['type'], $value['size'], $value['error']);
                    $file->uid = md5($this->user->id.$file->fileName.$file->fileSize.TIME);
                    $file->createdAt = TIME;
                    $file->createdBy = $this->user->id;
                    $file->entityType = 'ticket';

                    if ($file->save()) {
                        $path = Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file->uid;
                        $file->fileItem->saveAs($path);
                        $files[] = $file->id;
                    } else {
                        Response::ResponseError($file->getErrors());
                    }
                }
            } catch(Exception $e) {
                Response::ResponseError($e->getMessage());
            }
            
            $logMessage = 'Upload files with id: '.implode(',', $files);
            Loger::logUser(Yii::app()->user->id, $logMessage);
            Response::ResponseSuccess($files);
        } elseif(!is_null($text)) {
            //ticket
            $userId = Yii::app()->user->id;
            $ticket = Ticket::get($ticketId);
            Ticket::modify($ticket, array(
                'status' => $this->getParam('status', null),
                'files' => $this->getParam('files', null),
            ), $text, $userId, null);

            $logMessage = 'Reply for ticket with id "'.$ticketId.'" with message: '.$text;
            Loger::logAdmin(Yii::app()->user->id, $logMessage);
            
            Response::ResponseSuccess();
        } else {
            //headers
            $this->preflight();
        }
    }
}