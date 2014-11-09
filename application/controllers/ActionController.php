<?php
class ActionController extends Controller{
	function __construct(){
		parent::__construct();
		
		require 'application/models/ActionModel.php';
		$this->model = new ActionModel();
	}
	
	/**
	 * Blank function
	 * Actions may never be accesed directly
	 */
	public function main() {}
	
	/**
	 * Save a post into drafts
	 */
	public function savePost() {
		echo json_encode($this->model->savePost($_POST));
	}

	/**
	 * Publish a post
	 */
	public function publishPost() {
		echo json_encode($this->model->publishPost($_POST));
	}

	/**
	 * Share a draft
	 */
	public function shareDraft() {
		echo json_encode($this->model->shareDraft($_POST));
	}

	/**
	 * Delete a post
	 */
	public function deletePost() {
		echo json_encode($this->model->deletePost($_POST));
	}

	/**
	 * Profile functions
	 */
	public function saveProfile() {
		echo json_encode($this->model->saveProfile($_POST));
	}

	/**
	 * Bookmark post
	 */
	public function bookmark() {
		echo json_encode($this->model->bookmark($_POST));
	}
	
	/**
	 * Recommend post
	 */
	public function recommend() {
		echo json_encode($this->model->recommend($_POST));
	}
	
	/**
	 * Upload image
	 */
	public function uploadImage() {
		echo json_encode($this->model->uploadImage($_POST, $_FILES));
	}
	
	/**
	 * Change avatar
	 */
	public function changeAvatar() {
		echo json_encode($this->model->changeAvatar($_POST, $_FILES));
	}
	
	/**
	 * Change background
	 */
	public function changeBackground() {
		echo json_encode($this->model->changeBackground($_POST, $_FILES));
	}


	/**
	 * Profile posts
	 */
	public function profilePosts() {
		echo json_encode($this->model->profilePosts($_POST));
	}

	/**
	 * Post list for drafts
	 */
	public function drafts() {
		echo json_encode($this->model->drafts($_POST));
	}

	/**
	 * Post list for shared drafts
	 */
	public function draftsShared() {
		echo json_encode($this->model->draftsShared($_POST));
	}

	/**
	 * Post list for bookmarked posts
	 */
	public function bookmarks() {
		echo json_encode($this->model->bookmarks($_POST));
	}

	/**
	 * Post list for home reading list
	 */
	public function readingList() {
		echo json_encode($this->model->readingList($_POST));
	}

	public function addComment() {
		echo json_encode($this->model->addComment($_POST));
	}

	public function getComments() {
		echo json_encode($this->model->getComments($_POST));
	}
}
?>