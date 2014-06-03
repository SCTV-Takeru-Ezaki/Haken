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
//画像合成クラス
//require_once(BASE_URI."/lib/PPM/ImageAnnotate.php");

$clientId = CLIENT_ID;
if(empty($clientId)){
	$retData = array("error" =>"クライアントIDが空です");
	$retJson = json_encode($retData);
	echo $retJson;
	$file->log("DUP!!");
	exit;

}

//------------------設定項目-------------------------
// リアルタイムフラグ チェックDEL時：1,チェックUP：0
define("REALTIME_FLAG", 0);

$mailflg = 1;

$path_to_json = "/home/".$clientId."/public_html/form/init/init.json";
//---------------------------------------------------
$file = &Log::factory('file', './log/out.log', 'POST.PHP');
$file->log("DUP CHK START");
if(duplicateChk()){
	$retData = array("error" =>"送信できませんでした。既にデータが送信されています。");
	$retJson = json_encode($retData);
	echo $retJson;
	$file->log("DUP!!");
	exit;
}

$postData = (isUrlEncoded($_POST))? urldecode_array($_POST) : $_POST;


$im = $postData["image"]; // 画像名
$title = $postData["enquete3"];//ニックネーム
$body = $postData["enquete4"];

//echo mb_convert_encoding($title, "UTF-8");
//echo mb_convert_encoding($body, "UTF-8");

if(REALTIME_FLAG){
    //$status = IMAGE_PUBLIC;
    $status = HEAP_PUBLIC;
}else{
    $status = UNCHECKED;
}


$htb = file_get_contents($path_to_json);
$jsondata = json_decode($htb,true);
$file->log($path_to_json);
//返信メール
if($mailflg){
define("CURRENT_MAIL_DIR", "/home/".$clientId."/msave");
require_once(CURRENT_MAIL_DIR."/common/common_msg.php");
require_once(CURRENT_MAIL_DIR."/lib/MailSave.php");
$ms = new MailSave();
}

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
	}
}else{
	$file->log("EMPTY POST!!");
	exit;
}

$file->log("EMAIL:{$EMAIL}");

// ID、midを取得
// midは現状idを利用しているのと、画像ファイル名にしているため、
// 先にidを取得しておく。
// getNewIdで取得できるIDはcommon.phpのSEQ_NAMEにて指定
$newId = $imPost->getNewId($db);
$file->log("$newId");
$mid = sprintf("%05d", $newId);

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
$testPath = "/home/".$clientId."/public_html/form/".$im;
$file->log("/home/".$clientId."/public_html/form/".$im);
//im $testPath;
$toPath = ORIG_DIR_PATH."/{$fileName}";
$file->log("to path:{$toPath}");
copy($testPath, $toPath);
chmod($toPath, 0666);

$toPath = RESIZE_DIR_PATH."/{$fileName}";

$file->log("r to path:{$toPath}");
$bool = $imPost->execImageRegist($fileName, $toPath);
if(!$bool){
	echo "画像が保存できませんでした。";
	$file->log("cant save image!!");
	exit;
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

//事後チェック２
if(REALTIME_FLAG){
	$options = array(
		XML_UNSERIALIZER_OPTION_ATTRIBUTES_PARSE => 'parseAttributes'
	);

	$unserializer = new XML_Unserializer($options);
	// 登録
	$ppmExec->execPublicImage($db, $mid, $fileName, $unserializer);

	flock($fp, LOCK_UN); // ロックの破棄
	fclose($fp);
}

// Flash用XML作成
$ppmExec->createXmlData($db);

$db->commit();

$returnId = "0{$mid}";

// メール送信
if($mailflg){
$ms->execAutoResponse($returnId, "", "", $EMAIL);
$datetime = date("Y-m-d H:i:s");
$ms->logs("send mail $returnId $co $datetime");
}

//各SNSのタイムラインへ投稿
switch($postData['snsName']){
	case 'facebook':
		$url="https://lunch.pitcom.jp/haken/facebook.php";
		$params = Array(
			'mode'  => 'post',
			'snsUid'  => $postData['snsUid'],
			'tokenSecret'  => $postData['tokenSecret']
		);
		sendPostQuery($url,$params);
		break;

	case 'twitter':
		$url="https://lunch.pitcom.jp/haken/tw_callback.php";
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
	$method = 'POST';
	$data = http_build_query($params);

	$options = array('http' => Array(
		'method' => $method,
	));

	// ステータスをチェック / PHP5専用 get_headers()
	$respons = get_headers($url);
	if(preg_match("/(404|403|500)/",$respons['0'])){
		return false;
		exit;
	}

	if($method == 'GET') {
		$url = ($data != '') ? $url.'?'.$data:$url;
	}else if($method == 'POST') {
		$options['http']['content'] = $data;
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