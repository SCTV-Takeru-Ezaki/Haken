<?php
class User{
	var $model;
	var $device;
	var $carrier;
	var $status;
	
	protected $userAgent;
	
	public function __construct($model){
		$this->model = $model;
		
		$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
		
		$this->setUserInfo();
		$this->initEncoding();
		$this->setPostedData();
	}
	private function initEncoding(){
		if($this->model->userInfo['CARRIER'] == 'kddi' && $this->model->userInfo['DEVICE'] == 'featurephone'){
			ini_set('mbstring.encoding_translation', 0);
			ini_set('mbstring.http_output', 'pass');
			ini_set('mbstring.http_input', 'pass');
		}
	}
	public function setPostedData(){
		//同じくPOSTデータも
		$this->model->postData = (!empty($_POST))? $_POST : false;
		//auフィーチャーフォン対策
		$this->model->postData = ($this->model->postData && Utility::isUrlEncoded($this->model->postData))? Utility::urldecode_array($this->model->postData) : $this->model->postData;

		//SJISのみの環境であれば内部エンコーディングに変換
		$this->model->postData = ($this->model->postData && Utility::isOnlySjisDevice($this->model->userInfo['CARRIER'],$this->model->userInfo['DEVICE']))? Utility::convertencoding_array($this->model->postData) : $this->model->postData;
		//GETデータを一旦格納
		if(!empty($_GET)){
			$get = (Utility::isUrlEncoded($this->model->postData))? Utility::urldecode_array($_GET) : $_GET;
			foreach($get as $key => $value){
				if(empty($this->model->postData[$key])) $this->model->postData[$key] = $value;
			}
		}
		//ステータスにあわせた」画像データを格納
		$this->model->postData['image'] = $this->setUploadFile($_FILES);
		$this->model->postData['image'] = ($this->model->postData && Utility::isUrlEncoded($this->model->postData['image']))? Utility::urldecode_array($this->model->postData['image']) : $this->model->postData['image'];
		foreach($this->model->init['enqueteList'] as $k => $enq){
			$name = $enq['NAME'];
			foreach($enq['ERROR_CHECK'] as $key => $prop){
				$value = (!empty($this->model->postData[$name]))?$this->model->postData[$name]:"";
				$checker = new Validator($key,$value,$this->model);
				$this->model->init['enqueteList'][$k]['ERROR_CHECK'][$key] = $checker->getResult();
			}
		}
		$this->model->postData = Utility::htmlspecialchars_array($this->model->postData);
	}
	private function setUploadFile($files){
		if(!is_dir(UPLOAD_DIR)){
			mkdir(UPLOAD_DIR,0707);
		}
		if(substr(sprintf('%o', fileperms(UPLOAD_DIR)), -4) != "0707"){
			chmod(UPLOAD_DIR,0707);
		}
		
		//画像削除だった場合
		if(preg_match("/削除/",$this->model->getValue($this->model->postData,'CMD'))){
			return false;
		}
		//SNSプラグインから画像を渡された場合
		if(!empty($_GET['image'])) return base64_decode($_GET['image']);
		//編集モードだった場合
		if(!empty($_POST['image']) && empty($files["image"]["tmp_name"])) return $_POST['image'];
		//通常投稿(確認画面)
		if(empty($_POST['image']) && !empty($files["image"]["tmp_name"])){
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $files["image"]["tmp_name"]);
			finfo_close($finfo);

			$new = "";

			foreach($this->model->init['allowExtensions'] as $k => $v){
				if(preg_match("/{$v}/",$mimeType)){
					$ext = $v;
					$new = UPLOAD_DIR.md5(uniqid($files["image"]["name"].rand(),1)).".{$v}";
					return @move_uploaded_file($files["image"]["tmp_name"], $new)?$new:false;
					break;
				}
			}
		}
		//初期画面だった場合
		return false;
	}
	private function getDevice(){
		if(preg_match('/android/i',$this->userAgent) && preg_match('/mobile/i',$this->userAgent) || preg_match('/iphone/i',$this->userAgent) || preg_match('/windows phone/i',$this->userAgent)){
			return 'smartphone';
		}elseif(preg_match('/android/i',$this->userAgent) || preg_match('/ipad/i',$this->userAgent)){
			return 'tablet'																									;
		}elseif($this->isCarrier()){
			return 'featurephone';
		}else{
			return 'pc';
		}
	}
	private function getCarrier(){
		if(preg_match('/docomo/i',$this->userAgent)){
			return 'docomo';
		}elseif(preg_match('/kddi/i',$this->userAgent)){
			return 'au';
		}elseif(preg_match('/softbank/i',$this->userAgent) || preg_match('/vodafone/i',$this->userAgent) || preg_match('/j-phone/i',$this->userAgent)){
			return 'softbank';
		}
	}
	private function isCarrier(){
		return ($this->getCarrier() != "")? true : false;
	}
	private function isOnlySjisDevice(){
		return ($this->carrier == 'kddi' && $this->device == 'featurephone')? true : false;
	}
	private function getStatus(){
		return (!empty($_GET))? $_GET : false;
	}
	private function setUserInfo(){
		$info = array();

		$this->model->userInfo = array(
					'UA' => $this->userAgent,
					'DEVICE' => $this->getDevice(),
					'CARRIER' => $this->getCarrier(),
					'STATUS' => $this->getStatus()
		);
	}
}
