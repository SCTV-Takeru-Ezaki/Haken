<?php
//新twitteroauth読み込み
// require 'vendor/autoload.php';
require 'lib/tw_autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;
require_once '../common.php';

$snsName='twitter';//
$mode=empty($_REQUEST['mode'])? '':$_REQUEST['mode'];
$snsId=empty($_REQUEST['snsUid'])? '':$_REQUEST['snsUid'];
$token=empty($_REQUEST['tokenSecret'])? '':$_REQUEST['tokenSecret'];
$imgMode=empty($_REQUEST['imgMode'])? '':$_REQUEST['imgMode'];//画像アップロード or プロフ画像

if($mode=='get'){
	// define('CALLBACK_URL', 'http://lunch.pitcom.jp/haken_test/tw_callback.php?mode='.$mode.'&imgMode='.$imgMode);
	$callback=OAUTH_CALLBACK.'?mode='.$mode.'&imgMode='.$imgMode;
}else if($mode=='post'){
	// define('CALLBACK_URL', 'http://lunch.pitcom.jp/haken_test/tw_callback.php?mode='.$mode);
	$callback=OAUTH_CALLBACK.'?mode='.$mode;
}

session_start();
//TwitterOAuth をインスタンス化
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);

//コールバックURLをここでセット
$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $callback));

//callback.phpで使うのでセッションに入れる
$_SESSION['oauth_token'] = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

//Twitter.com 上の認証画面のURLを取得( この行についてはコメント欄も参照 )
$url = $connection->url('oauth/authenticate', array('oauth_token' => $request_token['oauth_token']));

//Twitter.com の認証画面へリダイレクト
header( 'location: '. $url );