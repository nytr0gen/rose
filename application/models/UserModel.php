<?php
class UserModel extends Model {
	public function getBookmarksByUserId($userId) {
        $sql = 'SELECT p.ID as ID, post_title, post_subtitle, 
        			username, name, word_count, b.status as bookmark_status,
        			UNIX_TIMESTAMP(post_date) as post_date 
        		FROM posts p JOIN bookmarks b ON (b.post_id=p.ID AND b.user_id=post_author) 
        			JOIN users u ON (post_author=u.id)
	            WHERE u.id = :user_id AND post_status = "publish" AND b.status = 1
	            ORDER BY post_date DESC LIMIT 0, 10';
        $sth = $this->db->prepare($sql);
        $sth->execute(array('user_id' => $userId));

        $rows = array();
        while($row = $sth->fetch()) {
            $row['post_date_supertag'] = date('j<b\r>M', $row['post_date']);
            $row['reading_time'] = ceil($row['word_count'] / WORDS_PER_MINUTE);

            $rows[] = $row;
        }

        return $rows;
	}

	/**
	 * check login
	 */
	public function login($POST){
		$form = array(
				'status' => 1,
				'message' => '',
				'fields' => array()
			);
		
		if(empty($POST['username'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert an username!';
			$form['fields']['username'] = 1;
		}
		
		if($form['status'] == 1 && empty($POST['password'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert a password!';
			$form['fields']['password'] = 1;
		}
		
		if($form['status'] == 1){
			$username = $POST['username'];
			$password = $this->hashPassword($POST['password']);

			$query = $this->db->prepare("SELECT * FROM `users` WHERE `username` = :username AND `password` = :password");
			$query->execute(array('username' => $username,
								  'password' => $password));
			$row = $query->fetch();
			
			if(empty($row)) {
				$form['status']  = 0;
				$form['message'] = 'Incorrect username or password!';
				$form['fields']['username'] = 1;
				$form['fields']['password'] = 1;
			} else {
				$this->_login($username, $password);
				
				$this->putMsg('Successfully logged in');
				$this->redirect();
			}
		}
		
		return $form;
	}

	/**
	 * Logout user
	 */
	public function logout() {
		if (!isset($_COOKIE['SID'])) {
            return 0;
        }

        $this->session->delete($_COOKIE['SID']);
		
		$this->putMsg('Successfully logged out.');
		$this->redirect();
	}
	
	/**
	 * Join user
	 */
	public function join($POST){
		$form = array(
				'status' => 1,
				'message' => '',
				'fields' => array()
			);
		
		$POST = $this->cleanPostArray($POST);
		
		if(empty($POST['username'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert an username!';
			$form['fields']['username'] = 1;
		}
		
		if($form['status'] == 1 && !preg_match('/^[A-Za-z0-9_]{0,64}+$/', $POST['username'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert a valid username (without spaces and < 64 chars)!';
			$form['fields']['username'] = 1;
		}
		
		$getUserByUsername = $this->getUserByUsername($POST['username']);
		if($form['status'] == 1 && !empty($getUserByUsername)){
			$form['status'] = 0;
			$form['message'] = 'This username is already used!';
			$form['fields']['username'] = 1;
		}
		
		if($form['status'] == 1 && empty($POST['password'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert a password!';
			$form['fields']['password'] = 1;
		}

		if($form['status'] == 1 && empty($POST['email'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert an email!';
			$form['fields']['email'] = 1;
		}
		
		if($form['status'] == 1 && !filter_var($POST['email'], FILTER_VALIDATE_EMAIL)){
			$form['status'] = 0;
			$form['message'] = 'Please insert a valid email (example: test@domain.tld)!';
			$form['fields']['email'] = 1;
		}
		
		$getUserByEmail = $this->getUserByEmail($POST['email']);
		if($form['status'] == 1 && !empty($getUserByEmail)){
			$form['status'] = 0;
			$form['message'] = 'This email is already used!';
			$form['fields']['email'] = 1;
		}
		
		if($form['status'] == 1) {
			$username = $POST['username'];
			$password = $this->hashPassword($POST['password']);

			$sql = 'INSERT INTO `users` (`username`, `password`, `email`, `name`) 
					  VALUES (:username, :password, :email, :username)';
			$query = $this->db->prepare($sql);
			$query->execute(array('username' => $username,
								  'password' => $password,
								  'email'    => $POST['email']));
			
			$this->_login($username, $password);
			
			$this->putMsg('Successfully joined in');
			$this->redirect();
		}
		
		$form['POST'] = $POST;
		
		return $form;
	}

	/**
	 * Logs user in by username and password
	 * @param  string $username 
	 * @param  string $password Hashed password
	 * @return bool        
	 */
	private function _login($username, $password) {
        $sth = $this->db->prepare('SELECT id FROM users WHERE username = ? AND password = ? LIMIT 1');
        $sth->execute(array($username, $password));
        $row = $sth->fetch(PDO::FETCH_NUM);
        
        if ($row) {
            $this->session->make('SID', $row[0]);

            return 1;
        }

        return 0;
	}

	private function _isOk($var) {
	  	if (   isset($var[3])
            && !isset($var[64])
        ) {
            return true;
        }

        return false;
	}
	
	/**
	 * User settings
	 */
	public function settings($POST) {
		$form = array(
				'status' => 1, 
				'message' => '',
				'final-message' => array(),
				'fields' => array()
			);
		
		$POST = $this->cleanPostArray($POST);
		
		$currentPassword = $this->hashPassword($POST['current_password']);
		if ($form['status'] && $currentPassword != $GLOBALS['user']['password']) {
			$form['status']  = 0;
			$form['message'] = 'Wrong current password.';
			$form['fields']['current_password'] = 1;
		}
		
		if ($form['status'] && !empty($POST['new_password'])) {
			if ($form['status'] && !$this->_isOk($POST['new_password'])) {
				$form['status']  = 0;
				$form['message'] = 'Invalid new password';
				$form['fields']['new_password'] = 1;
			}

			if ($form['status'] && $POST['new_password'] != $POST['new_password_confirm']) {
				$form['status']  = 0;
				$form['message'] = 'New password confirmation mismatch';
				$form['fields']['new_password_confirm'] = 1;
			}

			if ($form['status']) {
				$newPassword = $this->hashPassword($POST['new_password']);
				$sql = 'UPDATE users SET password = :password WHERE id = :id LIMIT 1';
				$sth = $this->db->prepare($sql);
				$sth->execute(array('password' => $newPassword, 
									'id'       => $GLOBALS['user']['id']));

				$form['final-message']['password'] = 'Password changed successfully!';
			}
		}

		if (   $form['status'] 
		    && !empty($POST['email'])
			&& $POST['email'] != $GLOBALS['user']['email']
		) {
			if ($form['status'] && !$this->_isOk($POST['email'])) {
				$form['status']  = 0;
				$form['message'] = 'Invalid email';
				$form['fields']['email'] = 1;
			}

			if($form['status'] && !filter_var($POST['email'], FILTER_VALIDATE_EMAIL)){
				$form['status'] = 0;
				$form['message'] = 'Please insert a valid email (example: test@domain.tld)!';
				$form['fields']['email'] = 1;
			}
			
			$sql = 'SELECT 1 FROM users WHERE email = :email AND id != :id LIMIT 1';
			$sth = $this->db->prepare($sql);
			$sth->execute(array('email' => $POST['email'],
								'id'    => $GLOBALS['user']['id']));
			if ($form['status'] && $sth->fetch()) {
				$form['status'] = 0;
				$form['message'] = 'This email is already used!';
				$form['fields']['email'] = 1;
			}

			if ($form['status']) {
				$sql = 'UPDATE users SET email = :email WHERE id = :id LIMIT 1';
				$sth = $this->db->prepare($sql);
				$sth->execute(array('email' => $POST['email'], 
									'id'    => $GLOBALS['user']['id']));

				$GLOBALS['user']['email'] = $POST['email'];
				$form['final-message']['email'] = 'Email changed successfully!';
			}
		}

		return $form;
	}
	
	/**
	 * User password reset request
	 */
	public function resetPasswordRequest($POST){
		$form = array(
				'status' => 1,
				'message' => '',
				'fields' => array()
			);

		if(!filter_var($POST['email'], FILTER_VALIDATE_EMAIL)){
			$form['status'] = 0;
			$form['message'] = 'Please insert a valid email!';
			$form['fields']['email'] = 1;
		}
		
		$getUserByEmail = $this->getUserByEmail($POST['email']);
		if($form['status'] == 1 && empty($getUserByEmail)){
			$form['status'] = 0;
			$form['message'] = 'This email doesn\'t exists in our database!';
			$form['fields']['email'] = 1;
		}

		if($form['status'] == 1){
			$getResetPasswordTokenByUserId = $this->getResetPasswordTokenByUserId($getUserByEmail['id']);

			if(!empty($getResetPasswordTokenByUserId)){
				$token = $getResetPasswordTokenByUserId['token'];
			} else {
				$token = md5(uniqid().rand(1, 99).date('Y-m-d H:i:s'));

				$query = $this->db->prepare("INSERT INTO `reset_password_tokens` (`user`, `token`, `ip`, `status`, `date`) VALUES (:user, :token, :ip, :status, :date)");
				$query->execute(array(
					'user' => $getUserByEmail['id'],
					'token' => $token,
					'ip' => $_SERVER['REMOTE_ADDR'],
					'status' => 1,
					'date' => date('Y-m-d H:i:s')
				));
			}

			//mail($POST['email'], 'Reset password request', 'Please access this link to reset your password: '.SITE_URL.'user/reset-password/'.$token);
			
			$form['message'] = 'You will receive an email with the reset instructions!';
		}
		
		return $form;
	}

	/**
	 * User password reset change
	 */
	public function resetPasswordChange($POST){
		$form = array(
				'status' => 1,
				'message' => '',
				'fields' => array()
			);

		if(empty($POST['token'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert a token!';
		}

		$getResetPasswordTokenByToken = $this->getResetPasswordTokenByToken($POST['token']);
		if($form['status'] == 1 && empty($getResetPasswordTokenByToken)){
			$form['status'] = 0;
			$form['message'] = 'This token doesn\'t exists in our database!';
			
		}

		if($form['status'] == 1 && empty($POST['password'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert a password!';
			$form['fields']['password'] = 1;
		}

		if($form['status'] == 1){
			$query = $this->db->prepare("UPDATE `users` SET `password` = :password WHERE `id` = :id");
			$query->execute(array(
				'password' => $this->hashPassword($POST['password']),
				'id' => $getResetPasswordTokenByToken['user']
			));

			$query = $this->db->prepare("UPDATE `reset_password_tokens` SET `status` = :status WHERE `token` = :token");
			$query->execute(array(
				'status' => 0,
				'token' => $POST['token']
			));
			
			$form['message'] = 'Your password has been changed with success!';
		}

		return $form;
	}
	
	public function getUserById($id){
		$query = $this->db->prepare("SELECT * FROM `users` WHERE `id` = :id");
		$query->execute(array(
			'id' => $id
		));
		$row = $query->fetch();
		
		return $row;
	}
	
	public function getUserByUsername($username){
		$query = $this->db->prepare("SELECT * FROM `users` WHERE `username` = :username");
		$query->execute(array(
			'username' => $username
		));
		$row = $query->fetch();
		
		return $row;
	}
	
	public function getUserByEmail($email){
		$query = $this->db->prepare("SELECT * FROM `users` WHERE `email` = :email");
		$query->execute(array(
			'email' => $email
		));
		$row = $query->fetch();
		
		return $row;
	}
	
	public function getResetPasswordTokenByUserId($user){
		$query = $this->db->prepare("SELECT * FROM `reset_password_tokens` WHERE `user` = :user AND `status` = :status");
		$query->execute(array(
			'user' => $user,
			'status' => 1
		));
		$row = $query->fetch();
		
		return $row;
	}

	public function getResetPasswordTokenByToken($token){
		$query = $this->db->prepare("SELECT * FROM `reset_password_tokens` WHERE `token` = :token AND `status` = :status");
		$query->execute(array(
			'token' => $token,
			'status' => 1
		));
		$row = $query->fetch();
		
		return $row;
	}
	
	public function getDraftById($id){
		$query = $this->db->prepare("SELECT * FROM `drafts` WHERE `id` = :id");
		$query->execute(array(
			'id' => $id
		));
		$row = $query->fetch();
		
		return $row;
	}
}
?>