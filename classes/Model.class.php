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
		$this->init['allowExtensions'] = explode(",",$this->init['allowExtensions']);
	}
	public function setErrorMessage($json){
		$this->errorMessage = $json;
	}
	public function setUserInfo($userInfo){
		$this->userInfo = $userInfo;
	}
	public function getPostedValueFromKey($key){
		if(is_array($this->postData) && array_key_exists($key,$this->postData)){
			return $this->postData[$key];
		}
	}
	public function getPostedLabelFromKey($name,$value){
		foreach($this->init['enqueteList'] as $k => $v){
			if($v['NAME'] == $name && is_array($v['PROPS']['value'])){
				$key = array_search($value,$v['PROPS']['value']);
				return $v['PROPS']['label'][$key];
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




