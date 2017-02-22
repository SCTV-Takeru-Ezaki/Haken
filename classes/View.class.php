<?php
class View{
	var $model;
	var $templateHtml;

	public function __construct($model){
		$this->model = $model;

		$this->loadTemplate();
	}
	private function loadTemplate(){
		$this->templateHtml = file_get_html($this->model->init['templateDir'].$this->model->userInfo['STATUS']['page'].'.html', false, null, 1, -1, true, true, DEFAULT_TARGET_CHARSET, false);
	}

	public function display(){
		$this->createView();
	}

	private function createView(){
		
	}

	private function publish() {
		echo $this->templateHtml;
	}
}
