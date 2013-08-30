<?php
class JSONLoader extends View{
	var $jsonData;

	public function __construct($_url){
		$this->jsonData = json_decode(file_get_contents($_url),true);
	}

	public function getJsonData(){
		return $this->jsonData;
	}
}
