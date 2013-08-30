<?php
require_once('lib/autoload.php');

class Browser{
	public $device = '';
	public $brower = '';
	protected $userAgent;

	public function __construct(){
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
	}

	private function getDevice(){
		if(preg_match('/android/i',$userAgent) && preg_match('/mobile/i',$userAgent) || preg_match('/iphone/i',$userAgent) || preg_match('/windows phone/i',$userAgent)){
			$device = 'smartphone';
		}elseif(preg_match('/android/i',$userAgent) || preg_match('/ipad/i',$userAgent)){
			$device = 'tablet';
		}elseif(preg_match('/docomo/i',$userAgent) || preg_match('/kddi/i',$userAgent) || preg_match('/softbank/i',$userAgent) || preg_match('/vodafone/i',$userAgent) || preg_match('/j-phone/i',$userAgent)){
			$device = 'featurephone';
		}else{
			$device = 'pc';
		}
	}
}