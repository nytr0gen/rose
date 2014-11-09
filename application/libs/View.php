<?php
class View {
	private $Mustache;
	public  $content;
	
	function __construct() {
		$this->Mustache = new Mustache_Engine(array(
		    'loader' => new Mustache_Loader_FilesystemLoader('application/views/'),
		    'escape' => function($value) { return $value; }
		    // 'cache' => '/tmp/cache/mustache',
		    // 'cache_file_mode' => 0666, // Please, configure your umask instead of doing this :)
		    // 'cache_lambda_templates' => true,
		));
	}
	
	public function process($files, $data) {
		if (is_array($files)) {
			foreach($files as $key => $value){
				$this->content .= $this->Mustache->render($value, $data);
			}
		} else {
			$this->content .= $this->Mustache->render($files, $data);
		}
	}

	public function show($files, $data, $pageType = 'main') {
		$data['siteUrl']     = SITE_URL;
		$data['loggedIn']    = LOGGED_IN;
		$data['user']        = $GLOBALS['user'];

		$this->process($files, $data);
		
		$data['msg']         = $GLOBALS['msg'];
		$data['pageType']    = $pageType;
		$data['pageContent'] = $this->content;
	
		echo $this->Mustache->render('part.wrapper', $data);
	}

	public function showSmall($files, $data) {
		$this->show($files, $data, 'small');
	}

	public function showError($data) {
		$this->show('page.error', $data, 'small');
		exit();
	}
}
?>