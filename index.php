<?php
ini_set('display_errors',1);
ini_set('error_reporting', E_ALL);
mb_language("uni");
mb_internal_encoding("UTF-8");

//オートロードを有効に
require_once 'Log.php';
require_once 'lib/autoload.php';

define("POST_EXEC","post.php");
define("UPLOAD_DIR","uploads/");
define("UPLOAD_MAXSIZE",3);

define("INIT_FILE","init/init.json");
define("ERROR_MESSAGE_FILE","init/errorMessage.json");

define("PROTOCOL",((!empty($_SERVER['HTTPS']))?'https://':'http://'));

$d = (dirname($_SERVER['REQUEST_URI']) != "/")? dirname($_SERVER['REQUEST_URI']) : "/".basename($_SERVER['REQUEST_URI']);
define("HTTP_SCRIPT_DIR",PROTOCOL.$_SERVER['SERVER_NAME'].$d);

//モデルを構築し
$model = new Model();

//設定情報とエラメをロード。設定情報とエラメ情報をセット
$init = new JSONLoader(INIT_FILE);
$model->setInit($init->getJsonData());

$errorMessage = new JSONLoader(ERROR_MESSAGE_FILE);
$model->setErrorMessage($errorMessage->getJsonData());

//ユーザー情報をModelへセット
$user = new User($model);

//インターフェース構築＆表示
$view = new FormView($model);
$view->display();
exit;
