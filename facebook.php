<?php
//FacebookSDKを読み込む
require_once("./lib/facebook.php");
require_once("../common.php");

$snsName='facebook';
$mode=empty($_REQUEST['mode'])? '':$_REQUEST['mode'];
$snsId=empty($_REQUEST['snsUid'])? '':$_REQUEST['snsUid'];
$token=empty($_REQUEST['tokenSecret'])? '':$_REQUEST['tokenSecret'];
$id = empty($_REQUEST['id'])? '':$_REQUEST['id'];
$imgMode=empty($_REQUEST['imgMode'])? '':$_REQUEST['imgMode'];//画像アップロード or プロフ画像

// appId と secret は「マイアプリ」のページで確認可
// https://www.facebook.com/developers/apps.php
$facebook = new Facebook(array(
	'appId' => FB_APPID,
	'secret' => FB_SECRET,
));

if(empty($_REQUEST['mode']) || $_REQUEST['mode']==null){
	header("Location:facebook.php?mode=get");
	exit;
}

if($mode=='get'){
	//ログイン状態を取得
	$userObj = $facebook->getUser();
	if($userObj) {
		//ログインしている場合
		try{
			$user_profile = $facebook->api('/me?fields=email,id,name','GET');
			$access_token = $facebook->getAccessToken();
			// var_dump($user_profile);

			$userId = $user_profile['id'];
			$userName = $user_profile['name'];
			$email = $user_profile['email'];

			//プロフ画像投稿の場合
			if($imgMode == 'profile_img'){
				$imgUrl = "https://graph.facebook.com/{$userId}/picture?type=large";
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
					header("Location:index.php?page=input&image=".$imgDir."&snsName=".$snsName."&snsUid=".$userId."&tokenSecret=".$access_token);
					exit;
				}else{
				}
			}

			//画像アップロードの場合
			header("Location:index.php?page=input&snsName=".$snsName."&snsUid=".$userId."&tokenSecret=".$access_token);
			exit;

		}catch(FacebookApiException $e){
			//エラー発生時
			$login_url = $facebook->getLoginUrl(
					array('scope' => 'publish_actions,email')
			);
			header("Location:{$login_url}");
			//エラーログの書き出し
			error_log($e->getType());
			error_log($e->getMessage());
		}
	}else{
		//ログインしていない場合
		//ログインURL取得2
		$login_url = $facebook->getLoginUrl(
		array('scope' => 'publish_actions,email')
		);

		header("Location:{$login_url}");
	}
}else if($mode=='post'){

	$permissionFlg=false;
	$permissions = $facebook->api("/{$snsId}/permissions", 'GET', array('access_token' => $token));
	//旧仕様
	//$permissionFlg=array_key_exists('publish_actions', $permissions['data'][0]);

	//新仕様(ver2.0)

	foreach($permissions['data'] as $k=>$v){
			// $file->log("permArr:".$v["permission"]);//→publish_actions
			if($v["permission"] == 'publish_actions'){
				$permissionFlg = true;
			}
	}


	//facebookに$appMessageを投稿
	if($permissionFlg){
		$facebook->api(
					"/{$snsId}/feed",'POST',
					array(
					'message' => '',
					'link' => FB_LINK,
					'name' => FB_APPNAME,
					'picture' =>FB_SHAREIMAGE,
					'description' =>FB_APPDESCRIPTION,
		));
		echo "投稿完了";
		exit;
	}
	echo "投稿失敗";
	exit;
}