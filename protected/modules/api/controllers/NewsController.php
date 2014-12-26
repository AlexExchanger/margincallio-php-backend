<?php

class NewsController extends MainController {
 
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
    
    public function actionAll() {
        $data = array(
            'query' => $this->getParam('query'),
            'category' => $this->getParam('category'),
            'isActive' => true,
        );

        try {
            $result = News::getList($data, $this->paginationOptions);
        } catch (Exception $e) {
            print Response::ResponseError();
            exit();
        }

        print Response::ResponseSuccess($result);
    }

    public function actionNews() {
        $id = $this->getParam('id');

        try {
            $result = News::getOne($id);
        } catch (Exception $e) {
            print Response::ResponseError();
            exit();
        }

        print Response::ResponseSuccess($result);
    }

    public function actionGetPdf() {
        
        $id = $this->getParam('id', false);
        try {
            if(!$id) {
                throw new ExceptionWrongInputData();
            }

            include Yii::getPathOfAlias('webroot').'/protected/extensions/pdfConverter/mpdf.php';

            $news = News::model()->findByPk($id);

            if(!$news) {
                throw new NoDataException();
            }

            $html = $this->render('newsTemplate', array(
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