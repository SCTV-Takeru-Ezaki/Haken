<?php
require_once 'lib/simple_html_dom.php';
class MultiView extends View{
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
		$this->createMultiView();
	}

	private function createMultiView(){
		//foreach($templateHtml->find('textarea[name=textarea]') as $el){
		//	$el->rows = 50;
		//}
		//
		foreach($this->model->init['enqueteList'] as $enq){
			switch($enq['TYPE']){
				case 'FILE':
					$style = $this->createStyle();
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\">";
					break;
				case 'TEXT':
					$style = $this->createStyle();
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\">";
					break;
				case 'TEXTAREA':
					$style = $this->createStyle();
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\">";
					break;
				case 'SELECT':
					$tag = "<select name=\"{$enq['NAME']>";
					foreach($enq['TYPE']['PROPS']['label'] as $k =>  $v){
						$style = $this->createStyle();
						$tag .= "<option value=\"{$enq['TYPE']['PROPS']['value'][$k]}\"}\">{$v}</option>";
					}
					$tag .= "</select>";
					break;
				case 'RADIO':
					foreach($enq['TYPE']['PROPS']['label'] as $k =>  $v){
						$style = $this->createStyle();
						$tag = "<label><input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME'] value=\"{$enq['TYPE']['PROPS']['value'][$k]}\"}\" style=\"{$style}\">{$v}</label>";
					}
					break;
				case 'HIDDEN':
					$style = $this->createStyle();
					$tag = "<input type=\"{$enq['TYPE']}\" name=\"{$enq['NAME']}\" style=\"{$style}\">";
					break;
				default:
					break;
			}
			$this->templateHtml->find("span#{$enq['NAME']}")->innerText = $tag;
			//$this->templateHtml->find("[name={$enq['NAME']}]");
		}
		$this->publish();
	}

	private function publish() {
		echo $this->templateHtml;
		$this->templateHtml->clear();
	}

}
