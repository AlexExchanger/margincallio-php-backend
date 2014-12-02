<?php
namespace application\modules\commonStat\controllers;

class StatController extends \ApiController
{

    public function filters()
    {
        return ['postOnly', 'auth'];
    }


    public function actionGet()
    {
        $json = [
            'totalRegCount' => \Stat::getTotalRegCount(),
            'directRegCount' => \Stat::getDirectRegCount(),
            'referralRegCount' => \Stat::getReferralRegCount(),
            'verifiedWaitingForModerationCount' => \Stat::getVerifiedWaitingForModerationCount(),
            'verifiedRejectedCount' => \Stat::getVerifiedRejectedCount(),
            'verifiedAcceptedCount' => \Stat::getVerifiedAcceptedCount(),
        ];
        $this->json($json);
    }
}