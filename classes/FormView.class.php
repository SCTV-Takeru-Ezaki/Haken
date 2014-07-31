<?php
require_once 'lib/simple_html_dom.php';
class FormView{
	var $model;
	var $templateHtml;
	
	public function __construct($model){
		$this->model = $model;

		$this->loadTemplate();
	}
	private function loadTemplate(){
		$this->templateHtml = file_get_html($this->model->init['templateDir'].$this->model->userInfo['STATUS']['page'].'.html', false, null, -1, -1, true, true, DEFAULT_TARGET_CHARSET, false);
	}
	public function display(){
		switch($this->model->userInfo['STATUS']['page']){
			case "input":
				$this->createFormView();
				break;
			case "confirm":
				$this->createConfirmView();
				break;
			case "post":
				$this->createPostView();
				break;
			case "before":
			case "closed":
				$this->publish();
				if(!empty($html)){
					$html->clear();
				}

				break;
			default:
				header('Location: '.HTTP_SCRIPT_DIR.'/?page=input');
				break;
		}
	}
	private function createPostView(){
		//print_r($this->model->postData);
		//$post = $this->model->postData;
		$this->model->postData = (Utility::isOnlySjisDevice($this->model->userInfo['CARRIER'],$this->model->userInfo['DEVICE']) && !Utility::isUrlEncoded($this->model->postData))? Utility::convertencoding_array2($this->model->postData) : $this->model->postData;
		if(empty($_SERVER['HTTP_REFERER'])){
			header('Location: '.HTTP_SCRIPT_DIR.'/?page=input');
			exit;	
		}
		if(!empty($this->model->postData['edit'])){
			if(preg_match("/編集/",$this->model->postData['edit'])){
				echo $this->sendPostQuery(PROTOCOL.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?page=input',$this->model->postData);
				exit;	
			}
		}

		$tag = "";
		if(!empty($this->model->postData['image'])){
			$ext = explode(".",$this->model->postData['image']);
			$n = count($ext);
			$image = new Imagick($this->model->postData['image']);
			$image->thumbnailImage(400, 0);
			$this->model->postData['image'] = "data:image/".$ext[$n-1].";base64,".base64_encode($image);
		}
		if(empty($this->model->postData['snstype']) || empty($this->model->postData['snsid'])){
			$this->model->postData['snstype']	= (!empty($this->model->postData['snsName']))? $this->model->postData['snsName'] : '';
			$this->model->postData['snsid']	= (!empty($this->model->postData['snsUid']))? $this->model->postData['snsUid'] : '';
		}
		foreach($this->model->postData as $k => $v){
			//
			$tag .= "<input type=\"hidden\" name=\"$k\" value=\"$v\">";
		}

		//予約型
		//$result = $this->sendPostSocket($_SERVER['SERVER_NAME'],'/client/client.php',$this->model->postData);
		//旧方式　たぶん今後は使わない
		// $result = $this->sendPostQuery(HTTP_SCRIPT_DIR.'/'.POST_EXEC,$post);
		$this->model->postData = Utility::htmlspecialchars_decode_array($this->model->postData);
		$result = json_decode($this->sendPostQuery(HTTP_SCRIPT_DIR.'/'.POST_EXEC,$this->model->postData),true);

		if(!empty($result['id'])){
			$this->templateHtml->find('span[id=result]',0)->innertext = $result['id'];//$result['id'];$tag;
		}else{
			$this->templateHtml->find('span[id=resultText]',0)->innertext = $result['error'];//$result['id'];$tag;
		}



		$this->publish();
		if(!empty($html)){
			$html->clear();
		}
	}

	private function createConfirmView(){
		$this->checkWrongAccess();
	    /*
	    if (
	        !isset($_FILES['image']['error']) ||
	        is_array($_FILES['image']['error'])
	    ) {
	        throw new RuntimeException('Invalid parameters.');
	    }

	    // Check $_FILES['image']['error'] value.
	    switch ($_FILES['image']['error']) {
	        case UPLOAD_ERR_OK:
	            break;
	        case UPLOAD_ERR_NO_FILE:
	            throw new RuntimeException('No file sent.');
	        case UPLOAD_ERR_INI_SIZE:
	        case UPLOAD_ERR_FORM_SIZE:
	            throw new RuntimeException('Exceeded filesize limit.');
	        default:
	            throw new RuntimeException('Unknown errors.');
	    }
	    */

		foreach($this->model->init['enqueteList'] as $k => $enq){
			foreach($enq['ERROR_CHECK'] as $error => $value){
				if($value != 0 || $this->model->getValue($_POST,'CMD') == 'IMGDELETE'){
					echo $this->sendPostQuery(PROTOCOL.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?page=input',$this->model->postData);
					exit;
				}
			}

			$value = "";
			$label = "";
			$tag = "";
			$value = (!empty($this->model->postData[$enq['NAME']]))?$this->model->postData[$enq['NAME']]:'';
			switch($enq['TYPE']){
				case 'FILE':
					$tag = "<div class=\"pure-g-r\"><div id=\"uploadImage\" class=\"pure-u-1\"><img width=\"240\" class=\"pure-u\" src=\"{$this->model->postData[$enq['NAME']]}\"><input type=\"HIDDEN\" name=\"{$enq['NAME']}\" value=\"{$this->model->postData[$enq['NAME']]}\"></div></div>";
					break;
				case 'CHECKBOX':
					if(!empty($this->model->postData[$enq['NAME']])){
						foreach($this->model->postData[$enq['NAME']] as $ck => $cv){
							$label .= $this->model->getPostedLabelFromKey($enq['NAME'],$cv).",";
						}
						$label = rtrim($label, ",");
					}
					$tag = "<span id=\"{$enq['NAME']}Confirm\">{$label}</span>　<input type=\"hidden\" name=\"{$enq['NAME']}\" value=\"{$value}\">";
					break;
				case 'SELECT':
				case 'RADIO':
				case 'AGREE':
				case 'HIDDEN':
					$label = (!empty($this->model->postData[$enq['NAME']]))? $this->model->getPostedLabelFromKey($enq['NAME'],$this->model->postData[$enq['NAME']]):'';
					$tag = "<span id=\"{$enq['NAME']}Confirm\">{$label}</span><input type=\"hidden\" name=\"{$enq['NAME']}\" value=\"{$value}\">";
					break;
				default:
					//$value = $this->model->postData[$enq['NAME']];
					//echo "::::::".Utility::isUrlEncoded($value).":::::";
					$label = nl2br((!empty($this->model->postData[$enq['NAME']]))?$this->model->postData[$enq['NAME']]:'');
					$value = (Utility::isOnlySjisDevice($this->model->userInfo['CARRIER'],$this->model->userInfo['DEVICE']) && !Utility::isUrlEncoded($value))? urlencode($value) : $value;
					$tag = "<span id=\"{$enq['NAME']}Confirm\">{$label}</span>　<input type=\"hidden\" name=\"{$enq['NAME']}\" value=\"{$value}\">";
					break;
			}

			$el = $this->templateHtml->find("span#".$enq['NAME'],0);
			$el->innertext = "<span class=\"itemTitle\">{$enq['TITLE']}</span>".$tag;
		}

		$this->publish();
		if(!empty($html)){
			$html->clear();
		}
	}

	/**
	 * @url 送信先
	 * @contents データ
	 */
	private function sendPostQuery($url,$params = array()){
		$method = 'POST';
		$data = http_build_query($params);
		$ref = (!empty($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER']:'');
		$header = Array(	"Content-Type: application/x-www-form-urlencoded",
							"Referer: ".$ref,
							"User-Agent: ".$this->model->userInfo['UA'],
						    'Authorization: Basic '.base64_encode('pmt:8504')
			);
		$options = array('http' => Array(
			'method' => $method,
			'header'  => implode("\r\n", $header),
		));
	
		//ステータスをチェック / PHP5専用 get_headers()
		$respons = get_headers($url);
		if(preg_match("/(404|403|500)/",$respons['0'])){
			return false;
		}
	
		if($method == 'GET') {
			$url = ($data != '')?$url.'?'.$data:$url;
		}elseif($method == 'POST') {
			$options['http']['content'] = $data;
		}
		$content = file_get_contents($url, false, stream_context_create($options));
		
		return $content;
	}

	private function createFormView(){
		//HTMLをいっかい生成してから選択済みをチェックしてまた再生成する
		//非効率な気がしないでもない。

		$this->model->postData = Utility::htmlspecialchars_decode_array($this->model->postData);
		foreach($this->model->init['enqueteList'] as $enq){
			$tag = "";
			switch($enq['TYPE']){
				case 'FILE':
					$style = $this->createStyle();
					$exts = "";
					foreach($this->model->init['allowExtensions'] as $ext){
						$exts.="image/{$ext},";
					}
					$exts = rtrim($exts, ",");

					$deleteButton = '<br /><input type="submit" name="CMD" value="画像を削除">';

					if(!empty($this->model->postData[$enq['NAME']]) ){
						$tag = "<div class=\"pure-g-r\"><div id=\"uploadImage\" class=\"pure-u-1\"><img width=\"240\" src=\"{$this->model->postData[$enq['NAME']]}\"><input type=\"HIDDEN\" name=\"{$enq['NAME']}\" value=\"{$this->model->postData[$enq['NAME']]}\">{$deleteButton}</div></div>\n";
					}else{
						$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" accept=\"{$exts}\" style=\"{$style}\">\n";
					}

					break;
				case 'TEXT':
					//HTMLを生成
					$style = $this->createStyle();
					$value = (!empty($this->model->postData[$enq['NAME']]))? $this->model->postData[$enq['NAME']] : "";
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\" value=\"{$value}\">\n";

					//入力済み項目を反映させる
					$html = str_get_html($tag, true, true, DEFAULT_TARGET_CHARSET, false);
					$html->find('input',0)->value = $this->model->getPostedValueFromKey($enq['NAME']);
					$html->find('input',0)->class = "pure-input-1";
					$tag = $html->find('input',0);
					break;
				case 'TEXTAREA':
					//HTMLを生成
					$style = $this->createStyle();
					$value = (!empty($this->model->postData[$enq['NAME']]))? $this->model->postData[$enq['NAME']] : "";
					$tag = "<textarea name=\"{$enq['NAME']}\">{$value}</textarea>\n";

					//入力済み項目を反映させる
					$html = str_get_html($tag, true, true, DEFAULT_TARGET_CHARSET, false);
					$html->find('textarea',0)->value = $this->model->getPostedValueFromKey($enq['NAME']);
					$html->find('textarea',0)->class = "pure-input-1";
					$tag = $html->find('textarea',0);
					break;
				case 'SELECT':
					//HTMLを生成
					foreach($enq['PROPS']['label'] as $k =>  $v){
						$style = $this->createStyle();
						$tag .= "<option value=\"{$enq['PROPS']['value'][$k]}\">{$v}</option>\n";
					}
					
					//入力済み項目を反映させる
					$html = str_get_html($tag, true, true, DEFAULT_TARGET_CHARSET, false);
					$tag="";
					$k=0;
					$tag = "<select name=\"{$enq['NAME']}\">\n";
					foreach($html->find('option') as $el){
						if($this->model->getPostedValueFromKey($enq['NAME']) == $enq['PROPS']['value'][$k]){
							$el->selected = true;
						}
						$tag .= $el."\n";
						$k++;
					}
					$tag .= "</select>\n";
					break;
				case 'RADIO':
					//HTMLを生成
					foreach($enq['PROPS']['label'] as $k =>  $v){
						$style = $this->createStyle();
						$tag .= "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" value=\"{$enq['PROPS']['value'][$k]}\" style=\"{$style}\">";
					}

					//入力済み項目を反映させる
					$html = str_get_html($tag, true, true, DEFAULT_TARGET_CHARSET, false);
					$tag="";
					$k=0;
					foreach($html->find('input') as $el){
						if($this->model->getPostedValueFromKey($enq['NAME']) == $enq['PROPS']['value'][$k]){
							$el->checked = true;
						}
						$tag .= "<label>".$el."{$enq['PROPS']['label'][$k]}</label>\n";
						$k++;
					}

					break;
				case 'CHECKBOX':
					//HTMLを生成
					foreach($enq['PROPS']['label'] as $k =>  $v){
						$style = $this->createStyle();
						$tag .= "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}[]\" value=\"{$enq['PROPS']['value'][$k]}\" style=\"{$style}\">";
					}

					//入力済み項目を反映させる
					$html = str_get_html($tag, true, true, DEFAULT_TARGET_CHARSET, false);
					$tag="";
					$k=0;
					foreach($html->find('input') as $el){
						if(is_array($this->model->getPostedValueFromKey($enq['NAME']))){				
							foreach($this->model->getPostedValueFromKey($enq['NAME']) as $r){
								if($r == $enq['PROPS']['value'][$k]){
									$el->checked = true;
								}
							}
						}
						$tag .= "<label>".$el."{$enq['PROPS']['label'][$k]}</label>\n";
						$k++;
					}
					break;
				case 'AGREE':
					//HTMLを生成
					$style = $this->createStyle();
					$tag = "<input type=\"checkbox\" name=\"{$enq['NAME']}\" style=\"{$style}\" value=\"1\"> ";

					//入力済み項目を反映させる
					$html = str_get_html($tag, true, true, DEFAULT_TARGET_CHARSET, false);
					$tag="";
					$k=0;
					foreach($html->find('input') as $el){
						if($this->model->getPostedValueFromKey($enq['NAME']) == $enq['PROPS']['value'][$k]){
							$el->checked = true;
						}
						$tag .= " <label> ".$el."{$enq['PROPS']['label'][$k]}</label>\n";
						$k++;
					}
					break;
				case 'HIDDEN':
					$style = $this->createStyle();
					$value = (!empty($this->model->postData[$enq['NAME']]))? $this->model->postData[$enq['NAME']] : "";
					$value = (!empty($enq['PROPS']['value']))? $enq['PROPS']['value'] : "";
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\" value=\"{$value}\">\n";
					$html = str_get_html($tag, true, true, DEFAULT_TARGET_CHARSET, false);
					$html->find('input',0)->value = $this->model->getPostedValueFromKey($enq['NAME']);
					if($value == ""){//0716修正
						$html->find('input',0)->value = (!empty($enq['PROPS']['value'][0]))? $enq['PROPS']['value'][0] : "";
					}
					$tag = $html->find('input',0);
					break;
				default:
					break;
			}
			$el = $this->templateHtml->find("span#".$enq['NAME'],0);
			$em = ($this->model->postData['page'] != 'input')?$this->getErrorMessage($enq):'';
			$el->innertext = "<span class=\"itemTitle\">{$enq['TITLE']}</span> ".$tag."<span class=\"itemError\">{$em}</span>";
		}
		$this->publish();
		if(!empty($html)){
			$html->clear();
		}
	}

	//エラーメッセージを取得(つくりかけ)
	private function getErrorMessage($enq){
		$message = "";
		if(is_array($enq['ERROR_CHECK']) && !empty($enq['ERROR_CHECK'])){
			foreach($enq['ERROR_CHECK'] as $k => $v){
				//文字の差し替え
				if(preg_match("/%_MAX_%/", $this->model->errorMessage[$k])) $this->model->errorMessage[$k] = preg_replace("/%_MAX_%/", UPLOAD_MAXSIZE, $this->model->errorMessage[$k]);
				if(!empty($this->model->errorMessage[$k]) && $v == 1) $message .= $this->model->errorMessage[$k];
			}
			return $message;
		}
	}

	private function createStyle(){

	}

	private function checkWrongAccess(){
		if(empty($this->model->postData['submit']) && empty($this->model->postData['CMD'])) header('Location: '.HTTP_SCRIPT_DIR.'/?page=input');
	}

	private function publish() {
		//viewportを設定
		$vp = "";
		switch($this->model->userInfo['DEVICE']){
			case "featurephone":
				/*
				$this->templateHtml->find('html',0)->outertext = '<?xml version="1.0" encoding="utf-8"?>'.$this->templateHtml->find('html',0)->outertext;
				*/
				$vp = "width=device-width,initial-scale=1,user-scalable=no";
				break;
			case "smartphone":
				$vp = "width=device-width,initial-scale=1,user-scalable=no";
				break;
			case "tablet":
				$vp = "width=device-width,initial-scale=1,user-scalable=no";
				break;
			default :
			
				$this->templateHtml->find('html',0)->outertext = '<?xml version="1.0" encoding="utf-8"?>'.$this->templateHtml->find('html',0)->outertext;
				
				$vp = "";
				break;
		}
		$this->templateHtml->find("meta[name=viewport]",0)->content = $vp;

		//CSSをロード(現時点では共通)
		//$this->templateHtml = str_get_html($this->templateHtml);

		if(Utility::isOnlySjisDevice($this->model->userInfo['CARRIER'],$this->model->userInfo['DEVICE'])){
			//$this->templateHtml->find('meta[http-equiv*=Content-type]',0)->content = 'text/html; charset=shift_jis';
			$this->templateHtml->find('head',0)->innertext = $this->templateHtml->find('head',0)->innertext.'<meta http-equiv="Content-Type" content="text/html; charset=shift_jis" />';
			//ヘッダー出力 au対策
			header("Content-Type: text/html; charset=shift_jis");
			echo mb_convert_encoding($this->templateHtml, "SJIS", "UTF-8");
		}else{
			$this->templateHtml->find('head',0)->innertext = $this->templateHtml->find('head',0)->innertext.'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
			header("Content-Type: text/html; charset=utf-8");
			echo $this->templateHtml;
		}
		
		$this->templateHtml->clear();
	}

}
