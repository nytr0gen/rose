<?php
class HomeController extends Controller{
	function __construct(){
		parent::__construct();
		
		require 'application/models/HomeModel.php';
		$this->model = new HomeModel();
	}
	
	public function main() {
		global $URL;
		$data = array();

		if (   isset($URL[0])
			&& $this->model->userExists($URL[0])
		) { 
			$username = $URL[0];
			$data['profile'] = $this->model->getUserByUsername($username);

			$isProfileAuthor = ($data['profile']['id'] == $GLOBALS['user']['id']);
			$data['isProfileAuthor'] = $isProfileAuthor;
			if (   isset($URL[1])
				&& $URL[1] == 'edit'
				&& $isProfileAuthor
			) {
				$this->view->show('home/page.profile.edit', $data);
			} else {
				$this->view->show('home/page.profile', $data);
			}

			return;
		}
		
		$data['post_list'] = $this->model->getPublishedPosts();

		$this->view->show('home/page.main', $data);
	}
}
?>