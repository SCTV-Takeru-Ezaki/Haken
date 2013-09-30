<?php
require_once 'lib/simple_html_dom.php';
class FormView extends View{
	var $model;
	var $templateHtml;
	
	public function __construct($model){
		$this->model = $model;

		$this->loadTemplate();
	}
	private function loadTemplate(){
		$this->templateHtml = file_get_html($this->model->init['templateDir'].$this->model->userInfo['STATUS']['page'].'.html');
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
			default:
				header('Location: '.HTTP_SCRIPT_DIR.'/?page=input');
				break;
		}
	}
	private function createPostView(){
		if(!empty($this->model->postData['edit'])){
			if(preg_match("/編集/",$this->model->postData['edit'])){
				echo $this->sendPostQuery(PROTOCOL.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?page=input',$this->model->postData);
				exit;	
			}
		}
		$result = json_decode($this->sendPostQuery("HTTP_SCRIPT_DIR".POST_EXEC,$this->model->postData),true);
		$this->templateHtml->find('span[id=result]',0)->innertext = $result['id'];
		$this->publish();
		if(!empty($html)){
			$html->clear();
		}
	}

	private function createConfirmView(){
		$this->checkWrongAccess();

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
					$tag = "<div class=\"pure-g-r\"><div id=\"uploadImage\" class=\"pure-u-1\"><img class=\"pure-u\" src=\"{$this->model->postData[$enq['NAME']]}\"><input type=\"HIDDEN\" name=\"{$enq['NAME']}\" value=\"{$this->model->postData[$enq['NAME']]}\"></div></div>";
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
					$value = $this->model->postData[$enq['NAME']];
					$tag = "<span id=\"{$enq['NAME']}Confirm\">{$value}</span>　<input type=\"hidden\" name=\"{$enq['NAME']}\" value=\"{$value}\">";
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

		$header = Array(	"Content-Type: application/x-www-form-urlencoded",
							"Referer: ".$_SERVER['HTTP_REFERER'],
							"User-Agent: ".$this->model->userInfo['UA']
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
		foreach($this->model->init['enqueteList'] as $enq){
			$tag ="";
			switch($enq['TYPE']){
				case 'FILE':
					$style = $this->createStyle();
					$exts = "";
					foreach($this->model->init['allowExtensions'] as $ext){
						$exts.="image/{$ext},";
					}
					$exts = rtrim($exts, ",");
					if(!empty($this->model->postData[$enq['NAME']])){
						$tag = "<div class=\"pure-g-r\"><div id=\"uploadImage\" class=\"pure-u-1\"><img src=\"{$this->model->postData[$enq['NAME']]}\"><input type=\"HIDDEN\" name=\"{$enq['NAME']}\" value=\"{$this->model->postData[$enq['NAME']]}\"><input type=\"submit\" name=\"CMD\" value=\"画像を削除\"></div></div>\n";
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
					$html = str_get_html($tag);
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
					$html = str_get_html($tag);
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
					$html = str_get_html($tag);
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
					$html = str_get_html($tag);
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
					$html = str_get_html($tag);
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
					$html = str_get_html($tag);
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
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\" value=\"{$value}\">\n";
					$html = str_get_html($tag);
					$html->find('input',0)->value = $this->model->getPostedValueFromKey($enq['NAME']);
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
		//print_r($this->model->postData);
		if(empty($this->model->postData['submit']) && empty($this->model->postData['CMD'])) header('Location: '.HTTP_SCRIPT_DIR.'/?page=input');
	}

	private function publish() {
		//viewportを設定
		$vp = "";
		switch($this->model->userInfo['DEVICE']){
			case "featurephone":
				$vp = "width=device-width,initial-scale=1,user-scalable=no";
				break;
			case "smartphone":
				$vp = "width=device-width,initial-scale=1,user-scalable=no";
				break;
			case "tablet":
				$vp = "width=device-width,initial-scale=1,user-scalable=no";
				break;
			default :
				$vp = "";
				break;
		}
		$this->templateHtml->find("meta[name=viewport]",0)->content = $vp;

		//CSSをロード(現時点では共通)
		$el = $this->templateHtml->find("head",0);
		$el->innertext = $el->innertext.'<link rel="stylesheet" href="css/pure-min.css">';
		header("Content-Type: text/html; charset=utf-8");
		echo $this->templateHtml;
		$this->templateHtml->clear();
	}

}
