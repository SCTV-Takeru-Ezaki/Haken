<?php
ini_set('display_errors',1);
ini_set('error_reporting', E_ALL & ~E_NOTICE);
mb_language("uni");
mb_internal_encoding("UTF-8");

//オートロードを有効に
require_once 'Log.php';
require_once 'lib/autoload.php';
require_once 'lib/htpasswd.php';

define("POST_EXEC","post.php");
define("UPLOAD_DIR","uploads/");
define("UPLOAD_MAXSIZE",5);
define("THUMBNAIL_S_SIZE",512);

define("DEFAULT_LANG","ja");

//define("INIT_FILE","init/init.json");
//define("ERROR_MESSAGE_FILE","init/errorMessage.json");

define("PROTOCOL",((!empty($_SERVER['HTTPS']))?'https://':'http://'));

$d = (dirname($_SERVER['REQUEST_URI']) != "/")? dirname($_SERVER['REQUEST_URI']) : "/".basename($_SERVER['REQUEST_URI']);
define("HTTP_SCRIPT_DIR",PROTOCOL.$_SERVER['SERVER_NAME'].$d);

//$lang = get

//モデルを構築し
$model = new Model();
$user = new User();
$user->setModel($model);
//$model->setLang($user->getLang());

if(!empty($_GET['lang'])){
	setcookie('lang',$_GET['lang']);
}
if(!empty($_COOKIE['lang'])){
	$user->setLang($_COOKIE['lang']);
}

$initFile = "init/init.".$user->getLang().".json";
$errorMessageFile = "init/errorMessage.".$user->getLang().".json";

$initFile = checkLangFile($initFile);
$errorMessageFile = checkLangFile($errorMessageFile);	


//設定情報とエラメをロード。設定情報とエラメ情報をセット
$init = new JSONLoader($initFile);
$model->setInit($init->getJsonData());

$errorMessage = new JSONLoader($errorMessageFile);
$model->setErrorMessage($errorMessage->getJsonData());


$user->setting();

//インターフェース構築＆表示
$view = new FormView($model);
$view->display();
exit;

function checkLangFile($file){
	global $user;
	//
	if(file_exists($file)){
		return $file;
	}else if(!file_exists($file) && $user->getLang() != "ja" && file_exists(preg_replace("/\.([a-z]){2}\./",".en.",$file))){
		return preg_replace("/\.([a-z]){2}\./",".en.",$file);
	}else {
		return preg_replace("/\.([a-z]){2}\./",".ja.",$file);
	}

}
