<?php

class NewsController extends AdminController {
 
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }

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
           $logMessage = 'Add new news with title "'.$result->id.'"';
           Loger::logAdmin(Yii::app()->user->id, $logMessag, 'news');
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
           
           $logMessage = 'Modify news with id "'.$data['id'].'"';
           Loger::logAdmin(Yii::app()->user->id, $logMessage, 'news');
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
    
    public function actionGetPdf() {
        
        $id = Yii::app()->request->getParam('id', false);
        try {
            if(!$id) {
                throw new Exception();
            }

            include Yii::getPathOfAlias('webroot').'/protected/extensions/pdfConverter/mpdf.php';

            $news = News::model()->findByPk($id);

            if(!$news) {
                throw new Exception();
            }

            $html = $this->render('newsTemplate', array(
                'imgPath' => Yii::getPathOfAlias('webroot').'/protected/extensions/pdfConverter/examples/',
                'title' => $news->title,
                'content' => $news->content
            ), true);

            $mpdf=new mPDF('utf-8'); 

            $mpdf->SetDisplayMode('fullpage');

            $stylesheet = file_get_contents(Yii::getPathOfAlias('webroot').'/protected/extensions/pdfConverter/examples/mpdfstyleA4.css');
            $mpdf->WriteHTML($stylesheet,1);
            $mpdf->WriteHTML($html);
            $mpdf->Output();
            
        } catch (Exception $e) {
            print Response::ResponseError();
            exit();
        }

    }
    
}