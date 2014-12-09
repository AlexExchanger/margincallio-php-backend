<?php

class NewsController extends CController {
 
    public $paginationOptions;
    
    public function beforeAction($action) {
        parent::beforeAction($action);

        $this->paginationOptions['limit'] = Yii::app()->request->getParam('limit', false);
        $this->paginationOptions['offset'] = Yii::app()->request->getParam('offset', false);
        $this->paginationOptions['sort'] = Yii::app()->request->getParam('sort', false);
        
        return true;
    }
    
    public function actionAddNews() {
        
        $data = array(
            'title' => Yii::app()->request->getParam('title'),
            'content' => Yii::app()->request->getParam('content'),
            'preview' => Yii::app()->request->getParam('preview'),
            'category' => Yii::app()->request->getParam('category'),
            'releaseData' => Yii::app()->request->getParam('releaseData', false),
            'isActive' => Yii::app()->request->getParam('isActive', 0),
            'number' => Yii::app()->request->getParam('number', 1),
        );
        
       try {
           $result = News::create($data, Yii::app()->user->id);
       } catch(Exception $e) {
           print Response::ResponseError();
           exit();
       }
       
       print Response::ResponseSuccess();
    }
    
    public function actionModifyNews() {
        $data = array(
            'id' => Yii::app()->request->getParam('id'),
            'title' => Yii::app()->request->getParam('title'),
            'content' => Yii::app()->request->getParam('content'),
            'preview' => Yii::app()->request->getParam('preview'),
            'category' => Yii::app()->request->getParam('category'),
            'releaseData' => Yii::app()->request->getParam('releaseData', false),
            'isActive' => Yii::app()->request->getParam('isActive', 0),
            'number' => Yii::app()->request->getParam('number', 1),
        );
        
        
       try {
           $news = News::model()->findByPk($data['id']);
           if(!$news) {
               throw new Exception();
           }
           
           $result = News::modify($news, $data, Yii::app()->user->id, null);
       } catch(Exception $e) {
           print Response::ResponseError($e->getMessage());
           exit();
       }
       
       print Response::ResponseSuccess();
        
    }
    
    public function actionGetAllNews() {

        $data = array(
            'query' => Yii::app()->request->getParam('query'),
            'isActive' => Yii::app()->request->getParam('isActive'),
            'category' => Yii::app()->request->getParam('category'),
        );
        
        try {
           $result = News::getList($data, $this->paginationOptions);
       } catch(Exception $e) {
           print Response::ResponseError();
           exit();
       }
       
       print Response::ResponseSuccess($result);
        
    }

}