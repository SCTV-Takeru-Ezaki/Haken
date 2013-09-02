<?php
require_once 'lib/simple_html_dom.php';
class Validator{
	var $method;
	var $value;

	public function __construct($method,$value = ''){
		$this->method = $method;
		$this->value = $value;
	}

	public function getResult(){
		$method = 'is'.$this->method;
		return $this->$method($this->value);
	}

	private function isREQUIRE($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		if(!empty($value)){
			$r = 0;
			return $r;
		}else{
			return 1;
		}
	}
	private function isFILESIZE($value = ''){
		return (filesize($value)/1024/1024 < UPLOAD_MAXSIZE)?0:1;
	}
	private function isTEL($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		if(count(preg_match('/^\d{10,11}$/', $value, $m)) > 0){
			return 1;
		}else{
			return 0;
		}
	}
	private function isZIP($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);

		if(count(preg_match('/^\d{7}$/', $value, $m)) > 0){
			return 1;
		}else{
			return 0;
		}
	}
	private function isEMAIL($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		if(count(preg_match('/.+@.+\..+/', $value, $m)) > 0){
			return 1;
		}else{
			return 0;
		}
	}
}




