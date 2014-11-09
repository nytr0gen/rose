<?php
class Application{	
	function __construct() {
		global $URL;

		$URL = !empty($_GET['url']) ? $_GET['url'] : ''; 
		$URL = explode('/', stripslashes($URL), 3); 
		// Limita la 3. daca vreodata vom folosi mai multe marim ^^
		
		// Verifica argumentele din url daca sunt doar litere si -
		// Sincer nu stiu ce face else-ul pe care il aveai
		// **
		// Am scos str to lower deoarece vreau sa pot folosi savePost
		// si in principiu e nevoie de asa ceva doar daca umbla ei la link, si nu incurajez asa ceva
		foreach($URL as $k => $v) {
			if (!preg_match('/^[A-Za-z0-9-]+$/', $v)) {
				$URL[$k] = '';
			}
		}
		
		$controller = 'HomeController';
		if (!empty($URL[0])) {
			if (file_exists('application/controllers/' . ucfirst($URL[0]) . 'Controller.php')) {
				$URL[0] = ucfirst($URL[0]);
				$controller = $URL[0] . 'Controller';
			}
		}

		require 'application/controllers/'.$controller.'.php';
		$controller = new $controller();
		
		$action = (!empty($URL[1]) ? $URL[1] : '');
		$action = str_replace('-', '_', $action);
		if(method_exists($controller, $action)) {
			$controller->$action();
		} else {
			$controller->main();
		}	
	}
}
?>