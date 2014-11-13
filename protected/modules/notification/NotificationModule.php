<?php

class NotificationModule extends CWebModule
{
    public function init() {
        print_r('sdf'); die();
        parent::init();
        $this->setImport(array(
            'application.modules.notification.components.*',
            'application.modules.notification.views.*',
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
