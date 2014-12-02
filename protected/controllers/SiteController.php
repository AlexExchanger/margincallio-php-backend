<?php

class SiteController extends CController {

    public function actionIndex() {
        print "<b>This is just an api engine, not a site</b>";
    }
    
    public function actionError() {
        print "<b>Oops...</b>";
    }
}