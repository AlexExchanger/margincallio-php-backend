<?php

class VerificationController extends CController { 
    
    private $user = null;
    
    public function beforeAction($action) {
    
        if(!Yii::app()->user->isGuest) {
            $this->user = Yii::app()->user;
            return true;
        }
        
        print Response::ResponseError('Access denied');
        return false;
    }
    
    public function actionSendDocs() {
        $loaded = false;
        if(isset($_FILES['fileItem1']) && isset($_FILES['fileItem2'])) {
            $file_first = new File();
            $file_second = new File();
            
            $file_first->fileName = $_FILES['fileItem1']['name'];
            $file_first->fileSize = $_FILES['fileItem1']['size'];
            $file1 = $_FILES['fileItem1'];            
            $file_first->fileItem = new CUploadedFile($file1['name'], $file1['tmp_name'], $file1['type'], $file1['size'], $file1['error']);
            $file_first->uid = md5($this->user->id.$file_first->fileName.$file_first->fileSize);
            $file_first->createdAt = TIME;
            $file_first->createdBy = $this->user->id;
            $file_first->entityType = 'doc';
            
            
            $file_second->fileName = $_FILES['fileItem2']['name'];
            $file_second->fileSize = $_FILES['fileItem2']['size'];
            $file2 = $_FILES['fileItem2'];
            $file_second->fileItem = new CUploadedFile($file2['name'], $file2['tmp_name'], $file2['type'], $file2['size'], $file2['error']);
            $file_second->uid = md5($this->user->id.$file_second->fileName.$file_second->fileSize);
            $file_second->createdAt = TIME;
            $file_second->createdBy = $this->user->id;
            $file_second->entityType = 'doc';
            
            $file_first->save();
            if($file_first->save()) { 
                $path = Yii::getPathOfAlias('webroot').DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$file_first->uid;
                $file_first->fileItem->saveAs($path);
            } else {
                print Response::ResponseError($file_first->getErrors());
                exit();
            }
            
            $file_second->save();
            if($file_second->save()) { 
                $path = Yii::getPathOfAlias('webroot').DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$file_second->uid;
                $file_second->fileItem->saveAs($path);
            } else {
                print Response::ResponseError($file_first->getErrors());
                exit();
            }
            
            $ticket = Ticket::create(array(
                'title' => 'Verification',
                'department' => 'verification',
            ), 'New verification documents', $this->user->id, array($file_first, $file_second));
            
            Loger::logUser(Yii::app()->user->id, 'New verification documents');
            $loaded = true;
        }
        
        $this->render('index', array('loaded'=>$loaded));
    }
}