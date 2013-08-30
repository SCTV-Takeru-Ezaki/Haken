<?php
require_once 'lib/simple_html_dom.php';
class Model{
	var $init;
	var $errorMessage;
	var $status;
	var $userInfo;
	var $postData;

	public function __construct(){
		
	}

	public function setInit($json){
		$this->init = $json;
	}
	public function setErrorMessage($json){
		$this->errorMessage = $json;
	}
	public function setUserInfo($userInfo){
		$this->userInfo = $userInfo;
	}
	public function getPostedValueFromKey($key){
		if(is_array($this->postData)){
			if(array_key_exists($key,$this->postData)){
				return $this->postData[$key];
			}
		}
	}
	/* checker */
	public function isPostedDataFromKey($key){
		foreach($this->postData as $k => $v){
			if($k == $key && !empty($this->postData)){
				return true;
				break;
			}
		}
		return false;
	}
}




