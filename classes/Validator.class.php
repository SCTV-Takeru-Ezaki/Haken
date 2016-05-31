<?php
require_once 'lib/simple_html_dom.php';
class Validator{
	var $method;
	var $value;
	var $model;

	var $auto_convert;

	public function __construct($method,$value = '',$model,$auto_convert = false){
		$this->method = $method;
		$this->value = $value;
		$this->auto_convert = $auto_convert;

		$this->model = $model;
	}
	public function getResult(){
		$method = 'isnt'.$this->method;
		return $this->$method($this->value);
	}

	private function isntREQUIRE($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		if(!empty($value)){
			$r = 0;
			return $r;
		}else{
			return 1;
		}
	}
	private function isntFILETYPE($value = ''){
		$result = 1;
		if(!empty($value)){
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = strtolower(finfo_file($finfo, $value));
			finfo_close($finfo);

			$result = 1;
			foreach($this->model->init['allowExtensions'] as $k => $v){
				if(preg_match("/{$v}/",$mimeType)){
					$result = 0;
					break;
				}
			}
		}else{
			$result = 0;
		}
		return $result;
	}
	private function isntFILESIZE($value = ''){
		$m = $value/1024/1024;
		return ($value/1024/1024 < UPLOAD_MAXSIZE)?0:1;
	}
	//バリデータ　10または11ケタの数字以外でエラー
	//コンバート　全角スペース、数字を半角にコンバートしたあとスペースを削除
	private function isntTEL($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		$value = ($this->auto_convert)? preg_replace('/(\s)/','',mb_convert_kana($value,'sn')):$value;
		if(count(preg_match('/^\d{10,11}$/', $value, $m)) > 0){
			return 0;
		}else{
			return 1;
		}
	}
	//バリデータ　7ケタの数字以外でエラー
	//コンバート　全角スペース、数字を半角にコンバートしたあとスペースを削除
	private function isntZIP($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		$value = ($this->auto_convert)? preg_replace('/(\s)/','',mb_convert_kana($value,'sn')):$value;
		if(count(preg_match('/^\d{7}$/', $value, $m)) > 0){
			return 0;
		}else{
			return 1;
		}
	}
	//バリデータ　メールフォーマット以外でエラー
	//コンバート　全角スペース、英数字を半角にコンバートしたあとスペースを削除
	private function isntEMAIL($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		$value = ($this->auto_convert)? preg_replace('/(\s)/','',mb_convert_kana($value,'sa')):$value;
		if(preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/', $value)){
			return 0;
		}else{
			return 1;
		}
	}
	//バリデータ　半角数字6ケタ以外でエラー
	//コンバート　全角スペース、数字を半角にコンバートしたあとスペースを削除
	private function isntNUM6($value = ''){
		$name = __FUNCTION__;
		$value = ($this->auto_convert)? preg_replace('/(\s)/','',mb_convert_kana($value,'sa')):$value;
		if(preg_match("/^[0-9]+$/",$value) && strlen($value)==6 || strlen($value)==0){
			return 0;
		}else{
			return 1;
		}
	}
}
