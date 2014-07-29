<?php
$ua = $_SERVER['HTTP_USER_AGENT'];
if((strpos($ua,'iPhone')!==false)||(strpos($ua,'iPod')!==false)||(strpos($ua,'iPad')!==false)){
	print "<meta content=\"initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no\" name=\"viewport\" />";
}else if((strpos($ua,'Android')!==false)){
	if((strpos($ua,'Chrome')!==false)||(strpos($ua,'Firefox')!==false)){
		print "<meta content=\"initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no\" name=\"viewport\" />";
	}else{
		print "<meta content=\"width=device-width,user-scalable=no\" name=\"viewport\" />";
	}
}
?>
