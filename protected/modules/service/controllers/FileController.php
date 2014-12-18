<?php

class FileController extends CController
{

    public function filters()
    {
        return ['postOnly'];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!Service::checkRequest(Yii::app()->params['services']['file']['salt'])) {
            throw new SystemException('Wrong request sign', array('request' => $_POST));
        }
        return true;
    }


    public function actionUpload()
    {
        $uid = ArrayHelper::getFromArray($_POST['request'], 'uid');
        $fileName = ArrayHelper::getFromArray($_POST['request'], 'fileName');
        $fileSize = ArrayHelper::getFromArray($_POST['request'], 'fileSize');
        $mimeType = ArrayHelper::getFromArray($_POST['request'], 'mimeType');

        $file = File::create($uid, $fileName, $fileSize, $mimeType);
        $this->json(['uid' => $file->uid]);
    }
}