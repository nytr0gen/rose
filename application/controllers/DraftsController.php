<?php
class DraftsController extends Controller{
	function __construct() {
		parent::__construct();
		
		require 'application/models/DraftsModel.php';
		$this->model = new DraftsModel();
	}
	
	public function main() {
		global $URL;

		$data['post_list'] = $this->model->getPostsByUserId($GLOBALS['user']['id'], 'draft');

		$this->view->show('drafts/page.main', $data);
	}

	public function shared() {
		$data['post_list'] = $this->model->getPostsByUserId($GLOBALS['user']['id'], 'share');

		$this->view->show('drafts/page.shared', $data);
	}
}
?>