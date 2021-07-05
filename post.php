<?php
ini_set('display_errors',1);
ini_set('error_reporting', E_ALL);
// 投稿登録
// カレントの言語を設定する
mb_language("uni");
// 内部文字エンコードを設定する
mb_internal_encoding("UTF-8");
define('DUPLICATE_LOG','log/duplicateList.log');

require_once("../pitadmin/current.php"); // pitadminディレクトリ直下にあるcurrent.phpを指定

// 以下4ファイルは必ずrequireする。
require_once(BASE_URI."/common/common.php");
require_once(BASE_URI."/common/common_db.php");
require_once(BASE_URI."/lib/PPM/DB.php");
require_once(BASE_URI."/lib/PPM/ImagePost.php");
require_once(BASE_URI."/lib/PPM/Exec.php");
require_once(BASE_URI."/lib/PPM/Api.php");
require_once("XML/Serializer.php");
require_once("XML/Unserializer.php");
require_once 'Log.php';

$clientId = CLIENT_ID;
if(empty($clientId)){
	$retData = array("error" =>"クライアントIDが空です");
	$retJson = json_encode($retData);
	echo $retJson;
	exit;
}

//------------------設定項目-------------------------
define("REALTIME_FLAG", 0);// リアルタイムフラグ チェックDEL時：1,チェックUP：0
$mailflg = 0;//自動返信メールの有無　有：1,無：0(VM上で作業行う場合など)
$formPath="/home/pituser/public_html/form/";//form設置先のパス
$facebook_clm=0;
// $formPath="/home/pituser/public_html/client/{$clientId}/form/";
//---------------------------------------------------

$path_to_json = $formPath."init/init.json";
$file = Log::factory('file', "/add_disk1/pituser/log/out.log", 'POST.PHP');

if(duplicateChk()){
	$retData = array("error" =>"送信できませんでした。既にデータが送信されています。");
	$retJson = json_encode($retData);
	echo $retJson;
	exit;
}
// 1投稿2POST現象対策(これによりIDを00001からスタート可)
if(empty($_POST)){
	$retData = array("error" =>"送信できませんでした。投稿データがありません。");
	$retJson = json_encode($retData);
	echo $retJson;
	exit;
}

$postData = (isUrlEncoded($_POST))? urldecode_array($_POST) : $_POST;


$im = $postData["imageFile"]; // 画像名
if(preg_match("/data:[^,]+,.+/i", $im)){
	$im = preg_replace("/data:[^,]+,/i","",$im);
	$im = base64_decode($im);
	$image = imagecreatefromstring($im);
	imagepng($image ,$formPath."uploads/".md5(implode("\t",$_POST)).".png");
	$im = "uploads/".md5(implode("\t",$_POST)).".png";
}
$title = $postData["enquete2"];//$postData["enquete4"];//ニックネーム
$body = $postData["enquete3"];//$postData["enquete5"];

//$postData["enquete2"] = !empty($postData["snsName"])?$postData["snsName"]:$postData["enquete2"];

if(REALTIME_FLAG){
    $status = IMAGE_PUBLIC;
    // $status = HEAP_PUBLIC;
}else{
    $status = UNCHECKED;
}


$htb = file_get_contents($path_to_json);
$jsondata = json_decode($htb,true);


$imPost = new PPM_ImagePost; // PPM_ImagePostクラスを利用するためにnewする。
$ppmExec = new PPM_Exec;

// データベースに接続
$newDb = new DB;
$db = $newDb->conn();
$db->beginTransaction();



//emailアドレスを取り出す
if(!empty($postData)){
	foreach($jsondata['enqueteList'] as $k=>$v){
		if(array_key_exists("EMAIL",$v["ERROR_CHECK"])){
			$key = $v["NAME"];
			$EMAIL = $postData[$key];
		}
		if($jsondata['enqueteList'][$k]['TYPE']=="FACEBOOK_AGREE"){
			$facebook_clm=$jsondata['enqueteList'][$k]['NAME'];
		}
	}
}else{
	$file->log("EMPTY POST!!");
	exit;
}

//返信メール
if($mailflg && !empty($EMAIL)){
	require_once(BASE_DIR."/lib/MailSave.php");
	$ms = new MailSave($db);
}

$file->log("EMAIL:{$EMAIL}");

// ID、midを取得
// midは現状idを利用しているのと、画像ファイル名にしているため、
// 先にidを取得しておく。
// getNewIdで取得できるIDはcommon.phpのSEQ_NAMEにて指定
$newId = $imPost->getNewId($db);
$file->log("$newId");
$mid = sprintf("%05d", $newId);
if(!$mid){
	$retData = array("error" =>"エラーが発生しました。時間をおいて投稿し直して下さい。");
	$retJson = json_encode($retData);
	echo $retJson;
	$file->log("Error: None ID.");
	exit;
}
$fileName = "{$mid}.jpg";

//事後チェック１
// API公開処理
// 最初にファイルロック
if(REALTIME_FLAG){
	$fp = @fopen(LOCK_FILE_PATH, "a+");
	if(flock($fp, LOCK_EX)){
		$nowDate = date("Y-m-d H:i:s");
		fputs($fp, "start {$nowDate} {$mid} => {$status}\n");
	}
}

// @todo 画像をアップロードした場所からorigフォルダ、resizeフォルダへコピー
$testPath = $formPath."".$im;
$file->log("r to path:{$toPath}");
$bool = $imPost->execImageRegist($fileName, $testPath);
if(!$bool){
	$retData = array("error" =>"エラーが発生しました。時間をおいて投稿し直して下さい。");
	$retJson = json_encode($retData);
	echo $retJson;
	$file->log("Error: No Image.");
	exit;
}

//事後チェック２
if(REALTIME_FLAG){
	$options = array(
		XML_UNSERIALIZER_OPTION_ATTRIBUTES_PARSE => 'parseAttributes'
	);
	$unserializer = new XML_Unserializer($options);

	// 上書き設定チェック
	if(REGIST_FLAG == 1){
		$bool = $ppmExec->chkFullImage($db); // 採用画像が全て埋まっているかどうかチェック
		/****
		* 全て埋まっている場合
		* ・一番古い採用画像を取得
		* ・ステータスをOLD_PUBLIC(上書き済み)に変更
		* ・APIから削除
		* ・新しいデータを登録
		* の作業を行う。
		* XMLはnumを空白にする以外は同じ。
		****/
		if($bool == 1){
			// 最も古い採用画像IDを取得
			$oldestMid = $ppmExec->getOldestImageMid($db);
			// APIに投げて画像データ削除
			// 管理画面側の採用データ情報は消さない
			$oldestFlg = 1;
			$oldestImgName = "{$oldestMid}.jpg";
			$ppmExec->execDeleteStatusAndImage($db, $oldestMid, $oldestImgName, $unserializer, $oldestFlg);
			// ステータスを上書き済みに変更
			$res = $ppmExec->execSetStatus($db, $oldestMid, OLD_PUBLIC);
			if(!$res){
				$error = $ppmExec->vdump($res);
				echo $error;
				$db->rollback();
				exit;
			}
		}
	}

	// 登録
	$ppmExec->execPublicImage($db, $mid, $fileName, $unserializer);

	flock($fp, LOCK_UN); // ロックの破棄
	fclose($fp);
}

// 基本データ登録用に配列に入れる
$arrPostData = array(
	"id" => $newId,
	"client_id" => CLIENT_ID,
	"mid" => $mid,
	"status" => $status,
	"mail_from" => $EMAIL,
	"title" => $title,
	"body" => $body,
	"img_name" => "{$mid}.jpg",
);

// DBに基本データを登録
// 基本的にtbl_dataテーブルは固定でカスタマイズしない。
// 登録できる内容は$arrPostDataにあるとおり。
// id, clientid, mid, statusは必須。
// 画像が存在しない場合は、img_nameを空白にしてstatusをNOIMAGEにする。
// statusの各値はcommon.phpのステータスを参照
$imPost->setTblData($db, $arrPostData);

// DBにオプションデータを登録
// enq_dataテーブルに登録するための関数。
// 最初に管理画面のオプションのアンケート追加にて項目を追加。
// そこで追加した項目の数字がenq_numの値となる。

foreach($jsondata['enqueteList'] as $k=>$v){
	$enq_num = mb_ereg_replace('[^0-9]', '', $v["NAME"]);
	$enq_text_key = $v["NAME"];

	$arrData = array(
		"client_id" => CLIENT_ID,
		"data_id" => $mid,
		"enq_num" => $enq_num,
		"enq_text" => $postData[$enq_text_key]
	);
	if($enq_num){
		$imPost->setOptData($db, $arrData);
	}
}


// Flash用XML作成
$ppmExec->createXmlData($db);

$db->commit();

$returnId = "0{$mid}";

// メール送信
if($mailflg){
$ms->execAutoResponse($returnId, $body, $title, $EMAIL);
$datetime = date("Y-m-d H:i:s");
$ms->logs("send mail $returnId $co $datetime");
}

$domain = $_SERVER['SERVER_NAME'];
//各SNSのタイムラインへ投稿
switch($postData['snsName']){
    case 'facebook':
        $url="https://{$domain}/form/facebook.php";
		$params = Array(
			'mode'  => 'post',
			'snsUid'  => $postData['snsUid'],
			'tokenSecret'  => $postData['tokenSecret']
		);
		// 「シェアする」へチェックあるときのみPOST、アンケート番号は適宜

		if(!empty($postData[$facebook_clm])){
			sendPostQuery($url,$params);
		}
		break;

	case 'twitter':
		$url="https://{$domain}/form/tw_callback.php";
		$params = Array(
			'mode'  => 'post',
			'snsUid'  => $postData['snsUid'],
			'tokenSecret'  => $postData['tokenSecret'],
			'id' => $returnId
		);
		sendPostQuery($url,$params);
		break;
	default:
		//通常投稿は何もしない
		break;
}
$file->log("echo IDs");

$retData = array("id" =>$returnId);
$retJson = json_encode($retData);
echo $retJson;

//FormViewクラスの関数(簡易版)
function sendPostQuery($url,$params = array()){
	$method = "POST";
	$data = http_build_query($params);

	$header = array(
        "Content-Type: application/x-www-form-urlencoded",
        "Content-Length: ".strlen($data),
        "Authorization: Basic ".base64_encode("pmt:1123")
    );

	$options = array("http" => Array(
		"method" => $method,
		"header" => implode("\r\n", $header)
	));

	// ステータスをチェック / PHP5専用 get_headers()
	$respons = get_headers($url);
	if(preg_match("/(404|403|500)/",$respons["0"])){
		return false;
		exit;
	}

	if($method == "GET") {
		$url = ($data != "") ? $url."?".$data:$url;
	}else if($method == "POST") {
		$options["http"]["content"] = $data;
	}
	$content = file_get_contents($url, false, stream_context_create($options));
	return $content;
}
function isUrlEncoded($array){
	if(!empty($array) && is_array($array)){
		foreach($array as $k=> $v){
			if(!empty($array[$k]) && !is_array($array[$k])){
				if(preg_match("/(%[0-9A-z]{2,3}){1,}/", $array[$k])){
					return true;
				}
			}
		}
	}else if(!empty($array)){
			if(preg_match("/(%[0-9A-z]{2,3}){1,}/", $array)){
				return true;
			}
	}

	return false;
}
function urldecode_array($array){
	foreach($array as $k=> $v){
		if(preg_match("/(%[0-9A-z]{2,3}){1,}/", $array[$k])){
			$encodedstr = preg_replace("/((%[0-9A-z]{2,3}){1,})/", "$0", $array[$k]);
			$array[$k] = urldecode($encodedstr);
		}
	}
	return $array;
}
function duplicateChk(){
	//最新の投稿データをハッシュ化
	$newTime = time();
	$newData = md5(implode("\t",$_POST))."\n";

	//過去データをロード
	$oldList = file(DUPLICATE_LOG);

	foreach($oldList as $k => $v){
		list($t,$h) = explode("\t",$v);
		$dif = $newTime - $t;
		if($newData == $h && $dif < 180){
			return true;
		}
	}
	array_unshift($oldList,"{$newTime}\t".$newData);

	$fp = fopen(DUPLICATE_LOG,"w+");
	$c = (count($oldList) <10)? count($oldList):10;
	if(flock($fp, LOCK_EX)){
		for($i=0;$i<$c;$i++){
			fwrite($fp,$oldList[$i]);
		}
	}
	return false;
}
