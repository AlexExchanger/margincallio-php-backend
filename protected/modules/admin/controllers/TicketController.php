<?php

class TicketController extends AdminController {
    
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        $this->paginationOptions['limit'] = Yii::app()->request->getParam('limit', false);
        $this->paginationOptions['offset'] = Yii::app()->request->getParam('offset', false);
        $this->paginationOptions['sort'] = Yii::app()->request->getParam('sort', false);
        
        return true;
    }

    public function actionViewActiveTickets() {        
        $userId = Yii::app()->request->getParam('userId', false);
        $status = Yii::app()->request->getParam('status', 'waitForSupport');
        
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
        $ticketId = Yii::app()->request->getParam('ticketId', false);
        
        try {
            $ticket = Ticket::model()->findByPk($ticketId);
            $tickets = array($ticket);
            Ticket::getUsers($tickets);
            
            $messages = Ticket::getMessageList($ticketId, $this->paginationOptions);
        } catch (Exception $e) {
            Response::ResponseError('Unknow error');
        }

        Response::ResponseSuccess(array(
            'ticket' =>  isset($tickets[0])? $tickets[0]:'',
            'messages' => $messages
        ));
    }
    
    private function view($type) {
        $userId = Yii::app()->request->getParam('userId', false);
        $status = Yii::app()->request->getParam('status', 'waitForSupport');
        
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
        $userId = Yii::app()->request->getParam('userId', false);
        $status = Yii::app()->request->getParam('status', false);
        
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
        
        $ticketId = Yii::app()->request->getParam('ticketId', false);
        $text = Yii::app()->request->getParam('text');
        
        if(!$ticketId) {
            Response::ResponseError();
        }
        
        try {
            $ticket = Ticket::get($ticketId);
            Ticket::modify($ticket, array(), $text, 0);
            
            $logMessage = 'Reply for ticket with id "'.$ticketId.'" with message: '.$text;
            Loger::logAdmin(Yii::app()->user->id, $logMessage);
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();   
    }
    
}