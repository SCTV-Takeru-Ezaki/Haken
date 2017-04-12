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
		$this->model->postData = (Utility::isUrlEncoded($this->model->postData))? Utility::urldecode_array($this->model->postData) : $this->model->postData;

		//SJISのみの環境であれば内部エンコーディングに変換
		$this->model->postData = (Utility::isOnlySjisDevice($this->model->userInfo['CARRIER'],$this->model->userInfo['DEVICE']))? Utility::convertencoding_array($this->model->postData) : $this->model->postData;
		//GETデータを一旦格納
		if(!empty($_GET)){
			$get = (Utility::isUrlEncoded($_GET))? Utility::urldecode_array($_GET) : $_GET;
			foreach($get as $key => $value){
				if(empty($this->model->postData[$key])) $this->model->postData[$key] = $value;
			}
		}

		//ステータスにあわせた」画像データを格納
		$files = (Utility::isUrlEncoded($_FILES))? Utility::urldecode_array($_FILES) : $_FILES;
		$this->model->postData['image'] = $this->setUploadFile($files);

		//バリデータチェック 各全半角の自動コンバートは
		foreach($this->model->init['enqueteList'] as $k => $enq){
			$name = $enq['NAME'];
			foreach($enq['ERROR_CHECK'] as $key => $prop){
				//ポストデータからチェックする値を抽出
				$value = (!empty($this->model->postData[$name]))?$this->model->postData[$name]:"";
				if($key == 'FILESIZE'){
					 $checker = new Validator($key,filesize($this->model->postData['image']),$this->model,$enq['ERROR_CHECK']['AUTO_CONVERT']);
				}else{
					$checker = new Validator($key,$value,$this->model,$enq['ERROR_CHECK']['AUTO_CONVERT']);
				}
				$this->model->init['enqueteList'][$k]['ERROR_CHECK'][$key] = $checker->getResult();
				error_log("EEROR:".$checker->getResult());
				//if($key == 'FILESIZE') error_log("FILESIZE CHECK:{$this->model->postData['image']}".filesize($this->model->postData['image']));
				if($key == 'FILETYPE') error_log("FILETYPE CHECK:{$this->model->postData['image']}/".$this->model->init['enqueteList'][$k]['ERROR_CHECK'][$key]);
			}
		}
//		print_r($this->model->init['enqueteList']);
		$this->model->postData = Utility::htmlspecialchars_array($this->model->postData);
	}
	private function setUploadFile($files){
		$post = (Utility::isUrlEncoded($_POST))? Utility::urldecode_array($_POST) : $_POST;
		$get = (Utility::isUrlEncoded($_GET))? Utility::urldecode_array($_GET) : $_GET;
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
		if(!empty($get['image'])) return base64_decode($get['image']);
		//編集モードだった場合
		if(!empty($post['image']) && empty($files["image"]["tmp_name"])) return $post['image'];
		//通常投稿(確認画面)
		if(empty($post['image']) && !empty($files["image"]["tmp_name"])){
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = strtolower(finfo_file($finfo, $files["image"]["tmp_name"]));
			finfo_close($finfo);

			$new = "";

			foreach($this->model->init['allowExtensions'] as $k => $v){
				$v = strtolower($v);
				if(preg_match("/{$v}/",$mimeType)){
					$ext = $v;
					$tmpName = UPLOAD_DIR.md5(uniqid($files["image"]["name"].rand(),1))."_tmp";
					// $tmp = UPLOAD_DIR.md5(uniqid($files["image"]["name"].rand(),1))."_tmp.{$v}";
					$tmp = $tmpName.".{$v}";
					$ori = UPLOAD_DIR.md5(uniqid($files["image"]["name"].rand(),1))."_ori".".{$v}";
					$new = UPLOAD_DIR.md5(uniqid($files["image"]["name"].rand(),1)).".{$v}";
					@move_uploaded_file($files["image"]["tmp_name"], $tmp)?$tmp:false;
					// 動画GIF対応(↑$tmpName追加,$tmp修正)
					$image = new Imagick();
					$image->readImage($tmp);
					$fNum = $image->getNumberImages();
					if($fNum > 2){
						$image->writeImages($tmp,false);
						$image->readImage($tmpName."-0.".$v);
						$image->writeImage($tmp);
						$image->clear();
						$delImagePath = "{$tmpName}-*.{$v}";
						system("rm -rf $delImagePath");
					}//動画GIF対応

					$this->orientationFixedImage($ori,$tmp);
					$image->readImage($ori);
					$size = ($image->getImageHeight() > $image->getImageWidth())? [0,THUMBNAIL_S_SIZE]:[THUMBNAIL_S_SIZE,0];

					$image->resizeImage($size[0],$size[1],Imagick::FILTER_LANCZOS, 1);
					$image->writeImage($new);
					$image->destroy();

					unlink($tmp);
					unlink($ori);
					return $new;
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
			return 'tablet';
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

		return $this->checkStatus();
	}
	private function checkStatus(){
		return (!is_array($this->checkTerm()))? ((!empty($_GET))? $_GET : false):$this->checkTerm();
	}
	private function checkTerm(){
		$conf = array('mode' => 0777, 'timeFormat' => '%X %x');
//		$display = &Log::singleton('display', '', '', $conf, PEAR_LOG_DEBUG);

		$format = '%Y-%m-%d %H:%M:%S';
		//開始日の日付フォーマットチェック
		if(!strptime($this->model->init['startDate'], $format)){
			$display->log('Format Error in init file at startData');
		}
		//終了日の日付フォーマットチェック
		if(!strptime($this->model->init['endDate'], $format)){
			$display->log('Format Error in init file at endData');
		}

		if(time()>=strtotime($this->model->init['startDate']) && time()<=strtotime($this->model->init['endDate'])){
			//期間内
			return true;
			//$display->log('true');
		}else if(time()<strtotime($this->model->init['startDate'])){
			//開始前
			//$display->log('before');
			return array("page"=>"before");
		}else{
			//終了
			//$display->log('closed');
			return array("page"=>"closed");
		}


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
	private function orientationFixedImage($output,$input){
		$output = "jpg:{$output}";
		$image = new Imagick($input);
		$exif_datas = @exif_read_data($input);
		if(isset($exif_datas['Orientation'])){
			  $orientation = $exif_datas['Orientation'];

			  if($image){
					  // 未定義
					  if($orientation == 0){
					  // 通常
					  }else if($orientation == 1){
					  // 左右反転
					  }else if($orientation == 2){
						$image->flopImage();
						$image->setimageorientation(imagick::ORIENTATION_TOPLEFT);
						$image->writeImage();
									  // 180°回転
					  }else if($orientation == 3){
						$image->rotateImage(new ImagickPixel(), 180);
						$image->setimageorientation(imagick::ORIENTATION_TOPLEFT);
						$image->writeImage();
					  // 上下反転
					  }else if($orientation == 4){
						$image->rotateImage(new ImagickPixel(), 270);
						$image->flopImage();
						$image->setimageorientation(imagick::ORIENTATION_TOPLEFT);
						$image->writeImage();
					  // 反時計回りに90°回転 上下反転
					  }else if($orientation == 5){
						$image->rotateImage(new ImagickPixel(), 90);
						$image->flopImage();
						$image->setimageorientation(imagick::ORIENTATION_TOPLEFT);
						$image->writeImage();
					  // 時計回りに90°回転
					  }else if($orientation == 6){
						$image->rotateImage(new ImagickPixel(), 90);
						$image->setimageorientation(imagick::ORIENTATION_TOPLEFT);
					  // 時計回りに90°回転 上下反転
					  }else if($orientation == 7){
						$image->rotateImage(new ImagickPixel(), 270);
						$image->flopImage();
						$image->setimageorientation(imagick::ORIENTATION_TOPLEFT);
						$image->writeImage();
					  // 反時計回りに90°回転
					  }else if($orientation == 8){
						$image->rotateImage(new ImagickPixel(), 270);
						$image->setimageorientation(imagick::ORIENTATION_TOPLEFT);
						$image->writeImage();
					  }
			  }
		}
		// 画像の書き出し
		$image->writeImage($output);
		return false;
	}

}
