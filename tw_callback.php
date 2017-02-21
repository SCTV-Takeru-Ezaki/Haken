<?php
session_start();

//新twitteroauth読み込み
// require 'vendor/autoload.php';
require 'lib/tw_autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;
require_once 'common.php';
require_once 'Log.php';

//キャンペーンサイトURL
$CAMPAGN_PAGE = '';

$snsName='twitter';
$mode=empty($_GET['mode'])? $_POST['mode']:$_GET['mode'];
$snsUid=empty($_POST['snsUid'])? '':$_POST['snsUid'];
$tokenSecret=empty($_POST['tokenSecret'])? '':$_POST['tokenSecret'];
$id = empty($_POST['id'])? '':$_POST['id'];
$imgMode=empty($_GET['imgMode'])? '':$_GET['imgMode'];//画像アップロード or プロフ画像

$file = &Log::factory('file', "/home/pituser/public_html/form/log/out.log", 'TW_CALLBACK.PHP');

$oauth_token = $_SESSION['oauth_token'];
$oauth_token_secret = $_SESSION['oauth_token_secret'];

$file->log("session oauth_token:".$oauth_token);
$file->log("mode:{$mode},{$tokenSecret},{$snsUid}");

if($CAMPAGN_PAGE == ''){
	print "キャンペーンサイトURLを設定してください";
	exit;
}

if($mode=='get' && empty($_GET['denied'])){
	$tw = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET,$oauth_token,$oauth_token_secret);
	// $access_token = $tw->getAccessToken($_GET['oauth_verifier']);
	try {
		$access_token = $tw->oauth("oauth/access_token", array("oauth_verifier" => $_GET['oauth_verifier']));
	} catch (Exception $e) {
	    echo 'エラーが発生しました: ',  $e->getMessage(), "<br />\n<a href=\"{$CAMPAGN_PAGE}\">キャンペーンページへ戻る</a>";
	}


	// print_r($access_token);//4データ取得->oauth_token,oauth_token_secret,user_id,screen_name
	//ユーザー情報取得
	$oauth_token = $access_token['oauth_token'];
	$oauth_token_secret = $access_token['oauth_token_secret'];
	$userId = $access_token['user_id'];
	$screen_name = $access_token['screen_name'];

	// $file->log("get oauth_token:".$oauth_token);

	// 認証後のユーザーデータ取得&登録用オブジェクト生成
	$connect = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET,$oauth_token,$oauth_token_secret);

	//ユーザーアイコン画像の場合
	if($imgMode == 'icon'){
		// 新TwitterOAuth用
		$show = $connect->get("users/show",array("screen_name"=>$screen_name));
		$imgUrl = $show->profile_image_url_https;
		$imgUrl = preg_replace("/_normal/","",$imgUrl);
		$path = "/home/pituser/public_html/form/";
		$dir = "uploads";
		$image = file_get_contents($imgUrl);

		if($image !=""){
			$fileName = $userId.'_'.md5(uniqid($userId.rand(),1)). '.jpg';//プロフ画像の変更があっても上書きされない
			$imgPath = $path.$dir."/".$fileName;
			$fp = fopen($imgPath, 'wb');
			$fwrite = fwrite($fp, $image);
			$fclose = fclose($fp);
			chmod($fileName,0777);

			$imgDir = base64_encode($dir."/".$fileName);
			header("Location:index.php?page=input&image=".$imgDir."&snsName=".$snsName."&snsUid=".$oauth_token."&tokenSecret=".$oauth_token_secret);
			exit;
		}else{
		}
	}
	header("Location:index.php?page=input&snsName=".$snsName."&snsUid=".$oauth_token."&tokenSecret=".$oauth_token_secret);
	exit;

}else if($mode=='post'){

	$file->log("post oauth_token(snsUid):".$snsUid);

	// 認証後のユーザーデータ取得&登録用オブジェクト生成
	$connect = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET,$snsUid,$tokenSecret);

	// Twitterへ画像アップロード
	$id = substr($id,1);
	//$imgPath = "https://gmabudokan.pitcom.jp/pitadmin/image/orig/{$id}.jpg";
//	$imgPath = "../pitadmin/image/orig/{$id}.jpg";
//	$mediaId = $connect->upload("media/upload",array("media"=>$imgPath));
//	$file->log("mediaId string:".$mediaId->media_id_string.",ID:",$id);
	$params = array(
		"status"=>SHARE_MSG."\n".SHARE_URL
//		"media_ids"=>$mediaId->media_id_string
	);

	//タイムラインに書き込み
	$post = $connect->post("statuses/update",$params);
	if(!empty($post->errors)){
		echo 'twへ投稿完了.res:'.$post->created_at;
		exit;
	}else{
		error_log("TwitterOAuth Error:{$post->errors[0]->message}",0);
	}
}else{
			header("Location: {$CAMPAGN_PAGE}");
}
