<?php
class PostController extends Controller{
	function __construct() {
		parent::__construct();
		
		require 'application/models/PostModel.php';
		$this->model = new PostModel();
	}
	
	public function main() {
		global $URL;
		$data = array();
		
		// Trick to get past "new" limitation
		if (!empty($URL[1]) && $URL[1] == 'new') {
			$this->newPost();
			return;
		}

		if (    empty($URL[1])
			|| !ctype_digit($URL[1])
		) { 
			$data['error'] = 'Invalid post id.';
			$this->view->showError($data);
		}

		$ID = $URL[1];
		$data['post'] = $this->model->getPostById($ID);

		$isPostAuthor = ($data['post']['post_author'] == $GLOBALS['user']['id']);
		$data['isPostAuthor'] = $isPostAuthor;
		if (    !$data['post']
			|| (   !$isPostAuthor 
				&& $data['post']['post_status'] != 'publish'
				&& $data['post']['post_status'] != 'share')
		) {
			$data['error'] = 'You are not allowed to perfom this action.';
			$this->view->showError($data);
		}

		if (!empty($URL[2]) && $URL[2] == 'edit') {
			if (!LOGGED_IN) {
				$this->model->putMsg('You have to login first');
				$this->model->redirect('user/login');
			}

			if (!$isPostAuthor) {
				$data['error'] = 'You are not allowed to perfom this action.';
				$this->view->showError($data);
			}

			$this->view->show('post/page.edit', $data);

			return;
		}

		$data['profile'] = $this->model->getUserByUserId($data['post']['post_author']);
		
		$data['user-recommend'] = $this->model->getRecommendsByPostId($data['post']['ID']);
		
		$getRecommends = $this->model->getRecommendsByPostIdAndLimit($data['post']['ID'], 4);
		$data['users-recommending'] = '';
		$k = count($getRecommends);
		for($i = 0; $i < $k; $i++){
			$data['users-recommending'] .= $getRecommends[$i]['name'].(($i < $k - 1) ? ', ' : '');
		}
		$data['commentsPerPage'] = COMMENTS_PER_PAGE;

		$this->view->show('post/page.main', $data);
	}

	public function newPost() {
		$data = array();
		
		if (!LOGGED_IN) {
			$this->model->putMsg('You have to login first');
			$this->model->redirect('user/login');
		}

		$data['post'] = array('post_title'    => '',
							  'post_subtitle' => '',
							  'post_content'  => '');

		$this->view->show('post/page.edit', $data);
	}
}
?>