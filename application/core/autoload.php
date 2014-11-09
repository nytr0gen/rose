<?php
function autoload($class){
	$className = explode('_', $class);
	
	if($className[0] == 'Mustache'){
		if($class[0] === '\\'){ $class = substr($class, 1); }

		$file = sprintf('%s/%s.php', 'application/libs', str_replace('_', '/', $class));

		if(file_exists($file)){ require $file; }
	} else {
		if(file_exists('application/libs/'.$class.'.php')){
			require 'application/libs/'.$class.'.php';
		} else {
			exit('The file ' . $class . '.php is missing...');
		}
	}
}

spl_autoload_register('autoload');
?>