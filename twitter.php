<?php
//twitteroauth読み込み
require_once('lib/twitteroauth.php');

$snsName='twitter';//
$mode=empty($_REQUEST['mode'])? '':$_REQUEST['mode'];
$snsId=empty($_REQUEST['snsUid'])? '':$_REQUEST['snsUid'];
$token=empty($_REQUEST['tokenSecret'])? '':$_REQUEST['tokenSecret'];
$imgMode=empty($_REQUEST['imgMode'])? '':$_REQUEST['imgMode'];//画像アップロード or プロフ画像

// developer画面 api 登録して取得した文字列を入れます
define('CONSUMER_KEY', 'RMXxP7kjXcy8MYQaDMSKQ');
define('CONSUMER_SECRET', 'gcfYSar3d0hPHfJA571YuiZ2F09Gt3V34E0oGlFwY');
if($mode=='get'){
	define('CALLBACK_URL', 'http://'.$_SERVER['SERVER_NAME'].'/form/tw_callback.php?mode='.$mode.'&imgMode='.$imgMode);
}else if($mode=='post'){
	define('CALLBACK_URL', 'http://'.$_SERVER['SERVER_NAME'].'/form/tw_callback.php?mode='.$mode);
}

// request token取得
$tw = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);

$token = $tw->getRequestToken(CALLBACK_URL);
if(! isset($token['oauth_token'])){
	echo "error: getRequestToken\n";
	exit;
}
session_start();
$_SESSION['oauth_token'] = $token['oauth_token'];
$_SESSION['oauth_token_secret'] = $token['oauth_token_secret'];

// 認証用URL取得してredirect
$authURL = $tw->getAuthorizeURL($token['oauth_token']);
header("Location: " . $authURL);

