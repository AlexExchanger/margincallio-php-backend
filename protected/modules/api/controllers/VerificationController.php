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
    
    public function actionGetFileForm() {        
        $model = new File();
        $loaded = false;
       
        if(isset($_POST['File'])) {
            $model->fileName = $_FILES['File']['name']['fileItem'];
            $model->fileSize = $_FILES['File']['size']['fileItem'];
            $model->fileItem = CUploadedFile::getInstance($model,'fileItem');
            $model->uid = md5($this->user->id.$model->fileName.$model->fileSize);
            $model->createdAt = TIME;
            $model->createdBy = $this->user->id;
            if($model->save()) { 
                if(count($model->fileItem) == 1) {
                    $path = Yii::getPathOfAlias('webroot').DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$model->uid;
                    $model->fileItem->saveAs($path);
                } else {
                    foreach($model->fileItem as $file) {
                        $path = Yii::getPathOfAlias('webroot').DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.md5($file->getName());
                        $file->saveAs($path);
                    }
                }
                $loaded = true;
            } else {
                print_r($model->getErrors()); die();
            }
        }
        
        $this->render('index', array('model'=>$model, 'loaded'=>$loaded));
    }
    
    
}