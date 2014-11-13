<?php

class AuthModule extends CWebModule
{
    public function init() {
        parent::init();
        $this->setImport(array(
            'application.modules.auth.controllers.*',
            'application.modules.auth.models.*',
        ));
    }

    public function beforeControllerAction($controller, $action) {

        if (parent::beforeControllerAction($controller, $action)) {
            return true;
        } else {
            return false;
        }

    }
}
