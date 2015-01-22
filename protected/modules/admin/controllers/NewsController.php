<?php

class NewsController extends AdminController {
 
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
    
    public function actionAddNews() {
        
        $data = array(
            'title' => $this->getParam('title'),
            'content' => $this->getParam('content'),
            'preview' => $this->getParam('preview'),
            'category' => $this->getParam('category'),
            'releaseData' => $this->getParam('releaseData', false),
            'isActive' => $this->getParam('isActive', 0),
            'number' => $this->getParam('number', 1),
        );
        
       try {
           $result = News::create($data, Yii::app()->user->id);
           $logMessage = 'Add new news with title "'.$result->id.'"';
           Loger::logAdmin(Yii::app()->user->id, $logMessage, 'news');
       } catch(Exception $e) {
           Response::ResponseError();
       }
       
       Response::ResponseSuccess();
    }
    
    public function actionModifyNews() {
        $data = array(
            'id' => $this->getParam('id', null),
            'title' => $this->getParam('title', null),
            'content' => $this->getParam('content', null),
            'preview' => $this->getParam('preview', null),
            'category' => $this->getParam('category', null),
            'releaseData' => $this->getParam('releaseData', false),
            'isActive' => $this->getParam('isActive', null),
            'number' => $this->getParam('number', null),
        );


        try {
            $news = News::model()->findByPk($data['id']);
            if (!$news) {
                throw new Exception();
            }
            $result = News::modify($news, $data, Yii::app()->user->id, null);

            $logMessage = 'Modify news with id "' . $data['id'] . '"';
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'news');
        } catch (Exception $e) {
            Response::ResponseError($e->getMessage());
        }

        Response::ResponseSuccess();
    }

    public function actionNews() {
        $id = $this->getParam('id', false);
        
        if(!$id) {
            Response::ResponseError();
        }
        
        try {
            $news = News::getOne($id, true);
        } catch(Exception $e) {
            if($e instanceof NoDataException) { 
                Response::ResponseError('No data');
            }
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($news);
    }
    
    public function actionAll() {
        $data = array(
            'query' => $this->getParam('query'),
            'isActive' => $this->getParam('isActive'),
            'category' => $this->getParam('category'),
        );

        try {
            $result = News::getFullList($data, $this->paginationOptions);
        } catch (Exception $e) {
            Response::ResponseError();
        }

        Response::ResponseSuccess(array(
            'count' => isset($this->paginationOptions['total']) ? $this->paginationOptions['total'] : '',
            'data' => $result
        ));
    }

    public function actionGetPdf() {
        
        $id = $this->getParam('id', false);
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
            Response::ResponseError();
        }
    }
}