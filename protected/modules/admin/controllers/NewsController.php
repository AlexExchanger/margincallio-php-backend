<?php

class NewsController extends AdminController {
 
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(mb_strtolower($action->id) == 'addnews' || mb_strtolower($action->id) == 'modifynews') {
            $this->preflight();
            return false;
        }
        if(!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);
        
        return true;
    }
    
    private function preflight() {
        $content_type = 'application/json';
        $status = 200;

        header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
        header('Access-Control-Allow-Credentials: true');
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        }
        header('Content-type: ' . $content_type);
    }
    
    public function actionAddNews() {
        
        $data = array(
            'title' => $this->getPost('title'),
            'content' => $this->getPost('content'),
            'preview' => $this->getPost('preview'),
            'category' => $this->getPost('category'),
            'releaseData' => $this->getPost('releaseData', false),
            'isActive' => $this->getPost('isActive', 0),
            'number' => $this->getPost('number', 1),
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
            'id' => $this->getPost('id', null),
            'title' => $this->getPost('title', null),
            'content' => $this->getPost('content', null),
            'preview' => $this->getPost('preview', null),
            'category' => $this->getPost('category', null),
            'releaseData' => $this->getPost('releaseData', false),
            'isActive' => $this->getPost('isActive', null),
            'number' => $this->getPost('number', null),
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