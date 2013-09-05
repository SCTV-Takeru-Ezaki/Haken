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
}
