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
			case "50":
				$this->createPostView();
				break;
			default:
				$this->createFormView();
				break;
		}
	}

	private function createConfirmView(){
		foreach($this->model->init['enqueteList'] as $k => $enq){
			foreach($enq['ERROR_CHECK'] as $error => $value){
				if($value != 0){
					$this->sendPostQuery("http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?page=input',$this->model->postData);
						exit;
				}
			}

			$value = 
			switch($enq['TYPE']){
				case 'SELECT':
					break;
				case 'RADIO':
					break;
				case 'CHECK':
					break;
				case 'AGREE':
					break;
				default:
					$value = $this->model->postData[$enq['NAME']];
					break;
			}
			$tag = "<span id=\"{$enq['NAME']}Confirm\">{$value}</span><input type=\"hidden\" name=\"{$enq['NAME']}\" value=\"{$value}\">";

			$el = $this->templateHtml->find("span#".$enq['NAME'],0);
			$el->innertext = "<span class=\"itemTitle\">{$enq['TITLE']}</span>".$tag;
		}


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
		//$options = array('http' => array(
		//	'method' => 'POST',
		//	'content' => http_build_query($contents),
		//));
		//echo file_get_contents($url, false, stream_context_create($options));
		//
		//
		$method = 'POST';
		$data = http_build_query($params);

		$header = Array(	"Content-Type: application/x-www-form-urlencoded",
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
	
		echo $content;
	}

	private function createFormView(){
		//HTMLをいっかい生成してから選択済みをチェックしてまた再生成する
		//非効率な気がしないでもない。
		foreach($this->model->init['enqueteList'] as $enq){
			$tag ="";
			switch($enq['TYPE']){
				case 'FILE':
					$style = $this->createStyle();
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\">\n";
					break;
				case 'TEXT':
					//HTMLを生成
					$style = $this->createStyle();
					$value = (!empty($this->model->postData[$enq['NAME']]))? $this->model->postData[$enq['NAME']] : "";
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\" value=\"{$value}\">\n";

					//入力済み項目を反映させる
					$html = str_get_html($tag);
					$html->find('input',0)->value = $this->model->getPostedValueFromKey($enq['NAME']);
					$tag = $html->find('input',0);
					break;
				case 'TEXTAREA':
					//HTMLを生成
					$style = $this->createStyle();
					$value = (!empty($this->model->postData[$enq['NAME']]))? $this->model->postData[$enq['NAME']] : "";
					$tag = "<textarea name=\"{$enq['NAME']}\">{$value}</textarea>\n";

					//入力済み項目を反映させる
					$html = str_get_html($tag);
					$html->find('input',0)->value = $this->model->getPostedValueFromKey($enq['NAME']);
					$tag = $html->find('input',0);
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
				case 'AGREE':
					//HTMLを生成
					$style = $this->createStyle();
					$tag = "<input type=\"checkbox\" name=\"{$enq['NAME']}\" style=\"{$style}\" value=\"1\">";

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
				case 'HIDDEN':
					$style = $this->createStyle();
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\">\n";
					$html = str_get_html($tag);
					$html->find('input',0)->value = $this->model->getPostedValueFromKey($enq['NAME']);
					$tag = $html->find('input',0);
					break;
				default:
					break;
			}
			$el = $this->templateHtml->find("span#".$enq['NAME'],0);
			//print_r($el->innertext);
			$em = $this->getErrorMessage($enq['NAME']);
			$el->innertext = "{$em}<span class=\"itemTitle\">{$enq['TITLE']}</span>".$tag;
		}
		$this->publish();
		if(!empty($html)){
			$html->clear();
		}
	}
	//エラーメッセージを取得(つくりかけ)
	private function getErrorMessage($name){
		return "";
	}

	private function createStyle(){

	}

	private function publish() {
		echo $this->templateHtml;
		$this->templateHtml->clear();
	}

}
