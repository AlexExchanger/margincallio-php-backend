<?php

class VerificationController extends MainController {

    private $user = null;
    private $fullControl = array('senddocs');

    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if (Yii::app()->user->isGuest && !in_array(mb_strtolower($action->id), $this->fullControl)) {
            Response::ResponseError('Access denied');
            return false;
        }

        $this->user = Yii::app()->user;
        return true;
    }

    public function actionPreflight() {
        $content_type = 'application/json';
        $status = 200;

        header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
        header('Access-Control-Allow-Credentials: true');
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        }
        header('Content-type: ' . $content_type);
    }

    public function actionSendDocs() {
        
        $docs = $this->getParam('files', null);
        
        if(isset($_FILES) && count($_FILES) > 0) {
            $user = User::model()->findByPk($this->user->id);
            
            if(!$user) {
                throw new Exception('User doesn\'t exist');
            }

            if($user->verifiedStatus == 'accepted') {
                throw new Exception('User already verified');
            }

            if($user->verifiedStatus != 'waitingForDocuments') {
                throw new Exception('Verification request submitted');
            }
            
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
                    $file->entityType = 'doc';

                    if ($file->save()) {
                        $path = Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file->uid;
                        $file->fileItem->saveAs($path);
                        $files[] = $file->id;
                    } else {
                        Response::ResponseError($file->getErrors());
                    }
                }
                Response::ResponseSuccess($files);
            } catch(Exception $e) {
                Response::ResponseError($e->getMessage());
            }
        } elseif(!is_null($docs)) {
            $user = User::model()->findByPk($this->user->id);
            
            try {
                if(!$user) {
                    throw new Exception('User doesn\'t exist');
                }

                if($user->verifiedStatus == 'accepted') {
                    throw new Exception('User already verified');
                }

                if($user->verifiedStatus != 'waitingForDocuments') {
                    throw new Exception('Verification request submitted');
                }

                $user->verifiedStatus = 'waitingForModeration';
                if(!$user->save(true, array('verifiedStatus'))) {
                    throw new Exception('Can\'t update user status');
                }

                $ticket = Ticket::create(array(
                    'title' => 'Verification',
                    'department' => 'verification',
                    'files' => $docs
                        ), 'New verification documents', $this->user->id, null);
            } catch(Exception $e) {
                Response::ResponseError($e->getMessage());
            }
            
            Loger::logUser(Yii::app()->user->id, 'New verification documents');
            Response::ResponseSuccess($ticket->id, 'Success');
        } else {
            $this->actionPreflight();
        }
    }

}
