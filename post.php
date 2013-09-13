<?php
error_reporting(E_ALL);
ini_set( 'display_errors', 1 );
// 投稿登録
// カレントの言語を設定する
mb_language("uni");
// 内部文字エンコードを設定する
mb_internal_encoding("UTF-8");
mb_detect_order("ASCII,JIS,EUC-JP,SJIS,UTF-8");

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

//画像合成クラス
//require_once(BASE_URI."/lib/PPM/ImageAnnotate.php");


$clientId = CLIENT_ID;

//------------------設定項目-------------------------
// リアルタイムフラグ チェックDEL時：1,チェックUP：0
define("REALTIME_FLAG", 0);

$mailflg = 1;

$path_to_json = "/home/".$clientId."/public_html/responsive/init/init.json";
//---------------------------------------------------

// POSTの値を取得
$im = $_POST["image"]; // 画像名
$title = $_POST["enquete2"];//ニックネーム
$body = $_POST["enquete3"];

if(REALTIME_FLAG){
    //$status = IMAGE_PUBLIC;
    $status = HEAP_PUBLIC;
}else{
    $status = UNCHECKED;
}

$htb = file_get_contents($path_to_json);
$jsondata = json_decode($htb,true);

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
if(!empty($_POST)){
	foreach($jsondata as $k=>$v){
		if(array_key_exists("EMAIL",$v["ERROR_CHECK"])){
			$key = $v["NAME"];
			$EMAIL = $_POST[$key];
		}
	}
}else{
	exit;
}


// ID、midを取得
// midは現状idを利用しているのと、画像ファイル名にしているため、
// 先にidを取得しておく。
// getNewIdで取得できるIDはcommon.phpのSEQ_NAMEにて指定
$newId = $imPost->getNewId($db);
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
$testPath = "/home/".$clientId."/public_html/responsive/".$echo;
//im $testPath;
$toPath = ORIG_DIR_PATH."/{$fileName}";
copy($testPath, $toPath);
chmod($toPath, 0666);

$toPath = RESIZE_DIR_PATH."/{$fileName}";
/*
$im = new Imagick();
$im->readImage($testPath);
$geo = $im->getImageGeometry(); //幅と高さを取得
$sizeX = $geo['width'];
$sizeY = $geo['height'];
if($sizeX >= $sizeY){
	$lenX = 0;
	$lenY = IMG_HEIGHT;
}
else{
	$lenX = IMG_WIDTH;
	$lenY = 0;
}

$im->resizeImage($lenX, $lenY, Imagick::FILTER_LANCZOS, 1); // リサイズ
$im->writeImage($toPath);
$im->clear();
$im->destroy();
chmod($toPath, 0666);
*/


$bool = $imPost->execImageRegist($fileName, $toPath);
if(!$bool){
	echo "画像が保存できませんでした。";
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

foreach($jsondata as $k=>$v){
	$enq_num = mb_ereg_replace('[^0-9]', '', $v["NAME"]);
	$enq_text_key = $v["NAME"];
	
	$arrData = array(
		"client_id" => CLIENT_ID,
		"data_id" => $mid,
		"enq_num" => $enq_num,
		"enq_text" => $_POST[$enq_text_key]
	);
	if($enq_num){
		$imPost->setOptData($db, $arrData);
	}
	//$result .="{$enq_text_key}=>{$_POST[$enq_text_key]}<br />\n";
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




//$ian = new PPM_ImageAnnotate;
//$url = $ian->setAnnotate($mid, $title, 8);
$url = "";
$retData = array("id" =>$returnId ,"url" => $url);
$retJson = json_encode($retData);

// メール送信
if($mailflg){
$ms->execAutoResponse($returnId, "", "", $EMAIL);
$datetime = date("Y-m-d H:i:s");
$ms->logs("send mail $returnId $co $datetime");
}

echo $retJson;
