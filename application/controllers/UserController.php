<?php
class UserController extends Controller{
	function __construct(){
		parent::__construct();
		
		require 'application/models/UserModel.php';
		$this->model = new UserModel();
	}
	
	public function main(){
		// dashboard...
	}

	public function bookmarks() {
		if (LOGGED_IN) {
			$data = array();
			$this->view->show('user/page.bookmarks', $data);
		} else {
			$this->model->putMsg('You must login to perfom this action');
			$this->model->redirect('user/login');
		}
	}

	public function login(){
		$data = array();
		
		if(!LOGGED_IN){
			if(isset($_POST['submit-login'])){
				$data['form-login'] = $this->model->login($_POST);
			}

			$this->view->show('user/page.login', $data, 'small');
		} else {
			$data['error'] = 'Error, you can\'t login twice!';
			
			$this->view->showError($data);
		}
	}
	
	public function logout(){
		if(LOGGED_IN){
			$this->model->logout();
		} else {
			$data = array();
			$data['error'] = 'Error, you can\'t logout while you are not logged in!';
			
			$this->view->showError($data);
		}
	}
	
	public function join(){
		$data = array();
		
		if(!LOGGED_IN){
			if(isset($_POST['submit-join'])){
				$data['form-join'] = $this->model->join($_POST);
			}
			
			$this->view->show('user/page.join', $data, 'small');
		} else {
			$data['error'] = 'Error, you can\'t join while you are logged in!';
			
			$this->view->showError($data);
		}
	}
	
	public function settings(){
		$data = array();
		
		if(LOGGED_IN){
			if(isset($_POST['submit-settings'])){
				$data['form-settings'] = $this->model->settings($_POST);
			}
			
			//$data['user'] = $this->model->getUserById($GLOBALS['user']['id']);
			
			$this->view->show('user/page.settings', $data, 'small');
		} else {
			$data['error'] = 'Error, you can\'t edit your account while you are not logged in!';
			
			$this->view->showError($data);
		}
	}
	
	public function reset_password(){
		$data = array();
		
		if(!LOGGED_IN){
			if(!empty($this->model->URL[2])){
				$getResetPasswordTokenByToken = $this->model->getResetPasswordTokenByToken($this->model->URL[2]);
			}

			if(empty($getResetPasswordTokenByToken)){
				if(isset($_POST['submit-reset-password-request'])){
					$data['form-reset-password-request'] = $this->model->resetPasswordRequest($_POST);
				}
				
				$this->view->show('user/page.reset-password-request', $data, 'small');
			} else {
				if(isset($_POST['submit-reset-password-change'])){
					$data['form-reset-password-change'] = $this->model->resetPasswordChange($_POST);
				}

				$data['token'] = $this->model->URL[2];

				$this->view->show('user/page.reset-password-change', $data, 'small');
			}
		} else {
			$data['error'] = 'Error, you can\'t use the forgot password system while you are logged in!';
			
			$this->view->showError($data);
		}
	}
	
	public function article(){
		$data = array();
		
		if(LOGGED_IN){
			switch($this->model->URL[2]){
				case 'create':
					if(isset($_POST['submit-article-create'])){
						$data['form-article-create'] = $this->model->createArticle($_POST);
					}
					
					$this->view->show('user/page.article.create', $data, 'small');
				break;
			}
		} else {
			$data['error'] = 'Error, you can\'t create an article while you are not logged in!';
			
			$this->view->showError($data);
		}
	}
}
?>