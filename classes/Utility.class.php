<?php
class Utility{

	public function __construct(){
	}

	public static function htmlspecialchars_array($input){
		array_walk_recursive(
			$input,
			create_function('&$value, $key', '$value=htmlspecialchars($value);')
		);
		return $input;
	}
	public static function isOnlySjisDevice($_carrier,$_device){
		return ($_carrier == 'au' && $_device == 'featurephone')? true : false;
	}
	public static function isUrlEncoded($array){
		foreach($array as $k=> $v){
			if(preg_match("/(%[0-9A-z]{2,3}){1,}/", $array[$k])){
				//echo "{$array[$k]}true";
				return true;
			}
		}
				//echo "{$array[$k]}false";
		return false;
	}
	public static function urldecode_array($array){
		foreach($array as $k=> $v){
			if(preg_match("/(%[0-9A-z]{2,3}){1,}/", $array[$k])){
				$encodedstr = preg_replace("/((%[0-9A-z]{2,3}){1,})/", "$0", $array[$k]);
				$array[$k] = urldecode($encodedstr);
			}
		}
		return $array;
	}
	/**
	 * [convertencoding_array Convert an array to internal encoding]
	 * @param  [array] $array [$_POST or $_GET]
	 * @return [array]        [converted array]
	 */
	public static function convertencoding_array($array){
		foreach($array as $k=> $v){
			$enc=mb_detect_encoding($array[$k]);
			//echo "ENC:".$enc;
			if($enc != mb_internal_encoding()){
				$array[$k]=htmlspecialchars($array[$k]);
				$array[$k]=mb_convert_encoding($array[$k], mb_internal_encoding(), $enc);
			}else{
				$array[$k]=$array[$k];
				$array[$k]=$array[$k];
			}
		}
		return $array;
	}

}
