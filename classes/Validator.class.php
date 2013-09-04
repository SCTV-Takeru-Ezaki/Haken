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
	private function isntFILESIZE($value = ''){
		return (filesize($value)/1024/1024 < UPLOAD_MAXSIZE)?0:1;
	}
	private function isntTEL($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		if(count(preg_match('/^\d{10,11}$/', $value, $m)) > 0){
			return 0;
		}else{
			return 1;
		}
	}
	private function isntZIP($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);

		if(count(preg_match('/^\d{7}$/', $value, $m)) > 0){
			return 0;
		}else{
			return 1;
		}
	}
	private function isntEMAIL($value = ''){
		$name = __FUNCTION__;
		$key = substr($name,2,strlen($name)-1);
		if(count(preg_match('/.+@.+\..+/', $value, $m)) > 0){
			return 0;
		}else{
			return 1;
		}
	}
}
