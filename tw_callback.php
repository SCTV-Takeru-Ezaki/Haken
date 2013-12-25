<?php
//twitteroauth読み込み
require_once('twitteroauth/twitteroauth.php');

$snsName='twitter';
$mode=empty($_REQUEST['mode'])? '':$_REQUEST['mode'];
$snsUid=empty($_REQUEST['snsUid'])? '':$_REQUEST['snsUid'];
$tokenSecret=empty($_REQUEST['tokenSecret'])? '':$_REQUEST['tokenSecret'];
$id = empty($_REQUEST['id'])? '':$_REQUEST['id'];
$imgMode=empty($_REQUEST['imgMode'])? '':$_REQUEST['imgMode'];//画像アップロード or プロフ画像

//@todo設定ファイルから読み込む
define('CONSUMER_KEY', 'RMXxP7kjXcy8MYQaDMSKQ');
define('CONSUMER_SECRET', 'gcfYSar3d0hPHfJA571YuiZ2F09Gt3V34E0oGlFwY');

$appMessage='ランチなう！メッセージをtwitterへ投稿。IDは'.$id.'です。';
$topPageURL='http://lunch.pitcom.jp/';

// access token 取得
session_start();
$oauth_token = $_SESSION['oauth_token'];
$oauth_token_secret = $_SESSION['oauth_token_secret'];

$tw = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET,$oauth_token,$oauth_token_secret);
$access_token = $tw->getAccessToken($_GET['oauth_verifier']);

// var_dump($access_token);//4データ取得->oauth_token,oauth_token_secret,user_id,screen_name
// exit;

if($mode=='get'){
	//ユーザー情報取得
	$oauth_token = $access_token['oauth_token'];
	$oauth_token_secret = $access_token['oauth_token_secret'];
	$userId = $access_token['user_id'];
	$screen_name = $access_token['screen_name'];

	//ユーザーアイコン画像の場合
	if($imgMode == 'icon'){
		$apiUrl = 'https://api.twitter.com/1.1/users/show.json';
		// $apiUrl = 'https://api.twitter.com/1.1/users/profile_banner.json';//->使えない!?
		$show = $tw->OAuthRequest($apiUrl,'GET',array("user_id"=>$userId,"screen_name"=>$screen_name));
		//ユーザーアイコンURL取得
		$show = json_decode($show,true);
		$imgUrl = $show["profile_image_url_https"];//or $show['profile_image_url']
		$path = "/home/lunch/public_html/haken/";
		$dir = "uploads";
		$image = file_get_contents($imgUrl);

		if($image !=""){
			$fileName = $userId.'_'.md5(uniqid($userId.rand(),1)). '.jpg';//プロフ画像の変更があっても上書きされない
			$imgPath = $path.$dir."/".$fileName;
			$fp = fopen($imgPath, 'wb');
			$fwrite = fwrite($fp, $image);
			$fclose = fclose($fp);
			chmod($fileName,0777);

			$imgDir = $dir."/".$fileName;
			header("Location:index.php?page=input&image=".$imgDir."&snsName=".$snsName."&snsUid=".$oauth_token."&tokenSecret=".$oauth_token_secret);
			exit;
		}else{
		}
	}
	//hakerへリダイレクト(画像アップロードの場合)
	header("Location:index.php?page=input&snsName=".$snsName."&snsUid=".$oauth_token."&tokenSecret=".$oauth_token_secret);
	exit;

}else if($mode=='post'){
	//タイムラインに書き込み
	// OAuthオブジェクトの生成
	$connect = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $snsUid, $tokenSecret);
	//$connect->format = "json";

	$twMessage = $appMessage."\n".$topPageURL;
	$api_url = "http://api.twitter.com/1.1/statuses/update.json";
	$method = "POST";
	$req = $connect->OAuthRequest($api_url,$method,array("status"=>$twMessage));

	echo 'twへ投稿完了';
	exit;
}