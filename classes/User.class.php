<?php
class User{
	var $model;
	var $device; 
	var $status;

	protected $userAgent;

	public function __construct($model){
		$this->model = $model;

		$this->userAgent = $_SERVER['HTTP_USER_AGENT'];

		$this->getStatus();
		$this->getDevice();

		$this->setUserInfo();
		$this->getPostedData();
	}
	public function getPostedData(){
		$this->model->postData = (!empty($_POST))? $_POST : false;
		$this->model->postData[key($_FILES)] = (!empty($_FILES))? $this->getUploadFile($_FILES) : false;

		if(!empty($_GET)){
			foreach($_GET as $key => $value){
				if(empty($this->model->postData[$key])) $this->model->postData[$key] = $value;
			}
		}

		foreach($this->model->init['enqueteList'] as $k => $enq){
			$name = $enq['NAME'];
			foreach($enq['ERROR_CHECK'] as $key => $prop){
				$value = (!empty($this->model->postData[$name]))?$this->model->postData[$name]:"";
				$checker = new Validator($key,$value);
				$this->model->init['enqueteList'][$k]['ERROR_CHECK'][$key] = $checker->getResult();
			}
		}
	}
	private function getUploadFile($files){
		$new = UPLOAD_DIR.md5(uniqid($files["image"]["name"].rand(),1)).".jpg";

		return @move_uploaded_file($files["image"]["tmp_name"], $new)?$new:false;
	}
	private function getDevice(){
		if(preg_match('/android/i',$this->userAgent) && preg_match('/mobile/i',$this->userAgent) || preg_match('/iphone/i',$this->userAgent) || preg_match('/windows phone/i',$this->userAgent)){
			$this->device = 'smartphone';
		}elseif(preg_match('/android/i',$this->userAgent) || preg_match('/ipad/i',$this->userAgent)){
			$this->device = 'tablet';
		}elseif(preg_match('/docomo/i',$this->userAgent) || preg_match('/kddi/i',$this->userAgent) || preg_match('/softbank/i',$this->userAgent) || preg_match('/vodafone/i',$this->userAgent) || preg_match('/j-phone/i',$this->userAgent)){
			$this->device = 'featurephone';
		}else{
			$this->device = 'pc';
		}
	}
	private function getStatus(){
		$this->status = (!empty($_GET))? $_GET : false;
	}
	private function setUserInfo(){
		$info = array();

		$this->model->userInfo = array(
					'UA' => $this->userAgent,
					'DEVICE' => $this->device,
					'STATUS' => $this->status
		);

		//return $info;
	}
}
