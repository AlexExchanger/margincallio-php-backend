<?php
defined('YII_DEBUG') or define('YII_DEBUG',true);

bcscale(15);
defined('TIME') or define('TIME', time());

$yii=dirname(__FILE__).'/./framework/yii.php';
$config=dirname(__FILE__).'/./protected/config/main.php';
require_once($yii);
Yii::createWebApplication($config)->run();