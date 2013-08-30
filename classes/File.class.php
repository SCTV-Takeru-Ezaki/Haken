<?php
require_once 'lib/simple_html_dom.php';
class Model{
	var $init;
	var $status;
	var $userInfo;

	public function __construct(){

	}

	public function setInit($init){
		$this->init = $init;
	}
	public function setUserInfo($userInfo){
		$this->userInfo = $userInfo;
	}
}




