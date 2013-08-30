<?php
ini_set('display_errors',1);
ini_set('error_reporting', E_ALL);

//オートロードを有効に
require_once 'lib/autoload.php';

define("UPLOAD_DIR","uploads/");
define("UPLOAD_MAXSIZE",5);

//モデルを構築し
$model = new Model();

//設定情報とエラメをロード。設定情報とエラメ情報をadd
$init = new JSONLoader('init/init.json');
$model->setInit($init->getJsonData());

$errorMessage = new JSONLoader('init/errorMessage.json');
$model->setErrorMessage($errorMessage->getJsonData());

//ユーザー情報をModelへadd
$user = new User($model);

//インターフェース構築
$view = new FormView($model);
$view->display();
exit;
