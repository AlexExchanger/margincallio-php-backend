<?php

class StatController extends AdminController {

    public $paginationOptions;

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->paginationOptions['limit'] = Yii::app()->request->getParam('limit', false);
        $this->paginationOptions['offset'] = Yii::app()->request->getParam('offset', false);
        $this->paginationOptions['sort'] = Yii::app()->request->getParam('sort', false);

        return true;
    }

    /*
      public function actionGet()
      {
      $json = [
      'totalRegCount' => Stat::getTotalRegCount(),
      'directRegCount' => Stat::getDirectRegCount(),
      'referralRegCount' => Stat::getReferralRegCount(),
      'verifiedWaitingForModerationCount' => Stat::getVerifiedWaitingForModerationCount(),
      'verifiedRejectedCount' => Stat::getVerifiedRejectedCount(),
      'verifiedAcceptedCount' => Stat::getVerifiedAcceptedCount(),
      ];
      $this->json($json);
      } */

    public function actionByFiatAddress() {

        $data = [
            'userId' => Yii::app()->request->getParam('userId'),
            'address' => Yii::app()->request->getParam('address'),
            
        ];
        
        try {
        $stat = Stat::getStatByFiat($data, [
            'common' => [
                'dateFrom' => Yii::app()->request->getParam('dateFrom'),
                'dateTo' => Yii::app()->request->getParam('dateTo'),
                'status' => Yii::app()->request->getParam('status'),
            ],
            'pagination' => $this->paginationOptions,
        ]);
        } catch(Exception $e) {
            print Response::ResponseError();
            exit;
        }
        
        print Response::ResponseSuccess($stat);
    }

}
