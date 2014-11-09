<?php
class ActionModel extends Model {
	public function addComment($POST) {
		$data = array('status' => 1);

		if (   !isset($POST['postId'])
			|| !ctype_digit($POST['postId'])
			|| !isset($POST['commText'])
			|| !$this->_postExists($POST['postId'])
		) {
			$data['status'] = 0;

			return $data;
		}

		$POST = $this->cleanPostArray($POST);


		$sql = 'INSERT INTO comments (post_id, comm_text, comm_author)
				VALUES (:postId, :commText, :commAuthor)';
		$sth = $this->db->prepare($sql);
		$sth->execute(array('postId'     => $POST['postId'],
							'commText'   => $POST['commText'],
							'commAuthor' => $GLOBALS['user']['id']));

		return $data;
	}

	private function _postExists($postId) {
		$sth = $this->db->prepare('SELECT 1 FROM posts WHERE ID = ? LIMIT 1');
		$sth->execute(array($postId));

		return !!($sth->fetch());
	}

	public function getComments($POST) {
		$data = array('status' => 1);

		if (   !isset($POST['postId'])
			|| !ctype_digit($POST['postId'])
			|| !$this->_postExists($POST['postId'])
		) {
			$data['status'] = 0;

			return $data;
		}

		$offset = 0;
		if (   isset($POST['offset']) 
			&& ctype_digit($POST['offset'])
		) { $offset = intval($POST['offset']); }

		$rowCount = COMMENTS_PER_PAGE;
		$offset   = $offset * COMMENTS_PER_PAGE;

		$sql = 'SELECT comm_date, name, avatar, comm_text, username
				FROM comments c JOIN users u ON (u.id = comm_author)
				WHERE post_id = :postId 
				ORDER BY comm_date DESC LIMIT :offset, :rowCount';
		$sth = $this->db->prepare($sql);
		$sth->bindValue(':postId',   $POST['postId'], PDO::PARAM_INT);
		$sth->bindValue(':offset',   $offset,         PDO::PARAM_INT);
		$sth->bindValue(':rowCount', $rowCount,       PDO::PARAM_INT);
		$sth->execute();

		$data['commentsList'] = $sth->fetchAll();

		return $data;
	}

    private function _getIdByUsername($username) {
        $sth = $this->db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $sth->execute(array($username));
        
        $ret = $sth->fetch(PDO::FETCH_NUM);
        $ret = $ret[0];

        return $ret;
    }

	public function profilePosts($POST) {
		$data = array('status' => 1);

		if (   isset($POST['username'])
			&& $this->userExists($POST['username'])
		) {
			$id = $this->_getIdByUsername($POST['username']);

			$offset = 0;
			if (   isset($POST['offset']) 
				&& ctype_digit($POST['offset'])
			) { $offset = intval($POST['offset']); }

			$data['post_list'] = $this->getPostsByUserId($id, 'publish', $offset);
			$data['status']    = !!($data['post_list']);
		} else {
			$data['status'] = 0;
		}

		return $data;
	}

	public function drafts($POST) {
		$data = array('status' => 1);

		if (LOGGED_IN) {
			$id = $GLOBALS['user']['id'];

			$offset = 0;
			if (   isset($POST['offset']) 
				&& ctype_digit($POST['offset'])
			) { $offset = intval($POST['offset']); }

			$data['post_list'] = $this->getPostsByUserId($id, 'draft', $offset);
			$data['status']    = !!($data['post_list']);
		} else {
			$data['status'] = 0;
		}

		return $data;
	}

	public function draftsShared($POST) {
		$data = array('status' => 1);

		if (LOGGED_IN) {
			$id = $GLOBALS['user']['id'];

			$offset = 0;
			if (   isset($POST['offset']) 
				&& ctype_digit($POST['offset'])
			) { $offset = intval($POST['offset']); }

			$data['post_list'] = $this->getPostsByUserId($id, 'share', $offset);
			$data['status']    = !!($data['post_list']);
		} else {
			$data['status'] = 0;
		}

		return $data;
	}

	public function bookmarks($POST) {
		$data = array('status' => 1);

		if (LOGGED_IN) {
			$id = $GLOBALS['user']['id'];

			$offset = 0;
			if (   isset($POST['offset']) 
				&& ctype_digit($POST['offset'])
			) { $offset = intval($POST['offset']); }

			$data['post_list'] = $this->_getBookmarksByUserId($id, $offset);
			$data['status']    = !!($data['post_list']);
		} else {
			$data['status'] = 0;
		}

		return $data;
	}

	public function readingList($POST) {
		$data = array('status' => 1);
		
		$offset = 0;
		if (   isset($POST['offset']) 
			&& ctype_digit($POST['offset'])
		) { $offset = intval($POST['offset']); }

		$data['post_list'] = $this->getPostsByUserId(-1, 'publish', $offset);
		$data['status']    = !!($data['post_list']);

		return $data;
	}

	private function _getBookmarksByUserId($userId, $offset = 0) {
    	$offset   = $offset * POSTS_PER_PAGE;
    	$rowCount = POSTS_PER_PAGE;

        $sql = 'SELECT p.ID as ID, post_title, post_subtitle, 
        			username, name, word_count, b.status as bookmark_status,
        			UNIX_TIMESTAMP(post_date) as post_date 
        		FROM posts p JOIN bookmarks b ON (b.post_id=p.ID) 
        			JOIN users u ON (post_author=u.id)
	            WHERE b.user_id = :userId AND post_status = "publish" AND b.status = 1
	            ORDER BY post_date DESC LIMIT :offset, :rowCount';
        $sth = $this->db->prepare($sql);
        $sth->bindValue(':userId',   $userId,   PDO::PARAM_INT);
        $sth->bindValue(':offset',   $offset,   PDO::PARAM_INT);
        $sth->bindValue(':rowCount', $rowCount, PDO::PARAM_INT);
        $sth->execute();

        $rows = array();
        while($row = $sth->fetch()) {
            $row['post_date_supertag'] = date('j<b\r>M', $row['post_date']);
            $row['reading_time'] = ceil($row['word_count'] / WORDS_PER_MINUTE);
			$row['bookmark_status'] = (($row['bookmark_status'] == 0) ? null : $row['bookmark_status']);

            $rows[] = $row;
        }

        return $rows;
	}

	/**
	 * Saves a post into db as draft
	 * @param  array $POST Usually $_POST
	 * @return array       Useful data for interpretation
	 */
	public function savePost($POST) {
		// Verifica daca valorile sunt goale. E important

		$POST['title']    = $this->cleanPostValue($POST['title']);
		$POST['subtitle'] = $this->cleanPostValue($POST['subtitle']);

		//tre sa filtrez tagurile din asta, sa accept doar unele pe care le vom folosi pt marcaj sexy
		$POST['content']  = trim($POST['content']);

		$ret = array();
		if (empty($POST['id'])) {
			$sql = 'INSERT INTO `posts` (`post_author`, `post_content`, `post_title`, 
						`post_subtitle`, `post_date`, `post_status`, `word_count`) 
					VALUES (:post_author, :post_content, :post_title, 
						:post_subtitle, :post_date, :post_status, :word_count)';
			$query = $this->db->prepare($sql);
			$query->execute(array(
				'post_author'   => $GLOBALS['user']['id'],
				'post_content'  => $POST['content'],
				'post_title'    => $POST['title'],
				'post_subtitle' => $POST['subtitle'],
				'post_date'     => date('Y-m-d H:i:s'),
				'post_status'   => 'draft',
				'word_count'    => str_word_count($POST['content'])
			));
			$ret['id'] = $this->db->lastInsertId('posts'); 
		} else {
			$sql = 'UPDATE `posts` 
					SET `post_content`  = :post_content, 
					    `post_title`    = :post_title,
					    `post_subtitle` = :post_subtitle,
					    `post_status`   = :post_status,
					    `word_count`    = :word_count
					WHERE `ID` = :ID AND `post_author` = :post_author LIMIT 1';
			$query = $this->db->prepare($sql);
			$query->execute(array(
				'ID'       => $POST['id'],
				'post_author'   => $GLOBALS['user']['id'],
				'post_content'  => $POST['content'],
				'post_title'    => $POST['title'],
				'post_subtitle' => $POST['subtitle'],
				'post_status'   => 'draft',
				'word_count'    => str_word_count($POST['content'])
			));
			$ret['id'] = $POST['id'];
		}
		
		$ret['date_saved'] = date('H:i:s');
		
		return $ret;
	}

	/**
	 * Changes post status to Publish
	 * @param  array $POST Gets the id
	 * @return bool        Successful or not
	 */
	public function publishPost($POST) {
		$sql = 'UPDATE `posts` SET `post_status` = :post_status
				WHERE `ID` = :ID AND `post_author` = :post_author LIMIT 1';
		$sth = $this->db->prepare($sql);

		$ret = $sth->execute(array('ID'          => $POST['id'],
								   'post_author' => $GLOBALS['user']['id'],
								   'post_status' => 'publish'));

		if ($ret) {
			$this->putMsg('Successfully published');
		}

		return $ret;
	}

	/**
	 * Shares a draft
	 * @param  array $POST
	 * @return array Status
	 */
	public function shareDraft($POST) {
		$sql = 'UPDATE posts SET post_status = "share"
				WHERE ID = :ID AND post_author = :post_author LIMIT 1';
		$sth = $this->db->prepare($sql);
		$ret = $sth->execute(array('ID'          => $POST['id'],
						           'post_author' => $GLOBALS['user']['id']));

		return array('status' => (bool)$ret);
	}

	/**
	 * Deletes a post
	 * @param  array $POST Gets the id
	 * @return bool        Successful or not
	 */
	public function deletePost($POST) {
		$sql = 'DELETE FROM posts WHERE ID = :ID AND post_author = :post_author LIMIT 1';
		$sth = $this->db->prepare($sql);

		$ret = $sth->execute(array('ID'          => $POST['id'],
								   'post_author' => $GLOBALS['user']['id']));

		if ($ret) {
			$this->putMsg('Successfully deleted');
		}
		
		return $ret;
	}

	/**
	 * [saveProfile description]
	 * @param  [type] $POST [description]
	 * @return [type]       [description]
	 */
	public function saveProfile($POST) {
		$POST = $this->cleanPostArray($POST);
		
		$sql = 'UPDATE users SET name = :name, bio = :bio WHERE id = :userId LIMIT 1';
		$sth = $this->db->prepare($sql);
		$ret = $sth->execute(array('name'   => $POST['name'],
								   'bio'    => $POST['bio'],
							       'userId' => $GLOBALS['user']['id']));

		if ($ret) {
			$this->putMsg('Profile successfully modified');

			$ret = array();
			$ret['msg']   = 'Successfully modified';
			$ret['status'] = 1;
		} else {
			$ret = array();
			$ret['msg']   = 'Failed to change';
			$ret['status'] = 0;
		}

		return $ret;
	}

	/**
	 * Sets a bookmark or unsets it
	 * @param  [type] $POST [description]
	 * @return [type]       [description]
	 */
	public function bookmark($POST) {
		$ret = array('status' => 1);

		if (!isset($POST['id'])
			|| !ctype_digit($POST['id'])
		) {
			$ret['msg']   = 'Invalid id';
			$ret['status'] = 0;

			return $ret;
		}

		$sql = 'INSERT INTO bookmarks (post_id, user_id, status)
				VALUES (:post_id, :user_id, (1))
				ON DUPLICATE KEY UPDATE status = (1 - status)';
		$sth = $this->db->prepare($sql);
		$sth->execute(array('post_id' => $POST['id'],
						    'user_id' => $GLOBALS['user']['id']));

		$sql = 'SELECT status FROM bookmarks 
				WHERE post_id = :post_id AND user_id = :user_id LIMIT 1';
		$sth = $this->db->prepare($sql);
		$sth->execute(array('post_id' => $POST['id'],
						    'user_id' => $GLOBALS['user']['id']));
		$row = $sth->fetch();
		$ret['bookmarked'] = $row['status'];

		return $ret;
	}
	
	/**
	 * Sets a recommend or unsets it
	 * @param  [type] $POST [description]
	 * @return [type]       [description]
	 */
	public function recommend($POST) {
		$ret = array('status' => 1);

		if (!isset($POST['id'])
			|| !ctype_digit($POST['id'])
		) {
			$ret['msg']   = 'Invalid id';
			$ret['status'] = 0;

			return $ret;
		}

		$sql = 'INSERT INTO recommends (post_id, user_id, status)
				VALUES (:post_id, :user_id, (1))
				ON DUPLICATE KEY UPDATE status = (1 - status)';
		$sth = $this->db->prepare($sql);
		$sth->execute(array('post_id' => $POST['id'],
						    'user_id' => $GLOBALS['user']['id']));

		$sql = 'SELECT status FROM recommends 
				WHERE post_id = :post_id AND user_id = :user_id LIMIT 1';
		$sth = $this->db->prepare($sql);
		$sth->execute(array('post_id' => $POST['id'],
						    'user_id' => $GLOBALS['user']['id']));
		$row = $sth->fetch();
		$ret['recommended'] = $row['status'];
		
		$query = $this->db->prepare("
			SELECT `users`.`name` FROM `recommends`
			RIGHT JOIN `users` ON `recommends`.`user_id` = `users`.`id`
			WHERE `recommends`.`post_id` = :post_id AND `recommends`.`status` = (:status)
			LIMIT 4
		");
		$query->execute(array(
			'post_id' => $POST['id'],
			'status' => 1
		));
		$rows = $query->fetchAll();
		$ret['users-recommending'] = $rows;

		return $ret;
	}
	
	public function changeAvatar($POST, $FILES){
		$form = array(
			'status' => 1,
			'message' => ''
		);
		
		if(empty($FILES['image']['tmp_name'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert an avatar!';
		}
		
		if($form['status'] == 1){
			$uploadImage = $this->uploadImage($POST, $FILES);
		}
		
		if($form['status'] == 1 && !isset($uploadImage['image-url'])){
			$form['status'] = 0;
			$form['message'] = $uploadImage['message'];
		}
		
		if($form['status'] == 1){
			$avatar = explode('/', $uploadImage['image-url']);
			$avatar = end($avatar);
			
			$query = $this->db->prepare("UPDATE `users` SET `avatar` = :avatar WHERE `id` = :id");
			$query->execute(array(
				'avatar' => $avatar,
				'id' => $GLOBALS['user']['id']
			));
			$GLOBALS['user']['avatar'] = $avatar;
			
			$form['message'] = 'Done!';
			$form['new-avatar'] = $uploadImage['image-url'];
		}
		
		return $form;
	}
	
	public function changeBackground($POST, $FILES){
		$form = array(
			'status' => 1,
			'message' => ''
		);
		
		if(!isset($POST['id']) || !ctype_digit($POST['id'])){
			$form['status'] = 0;
			$form['message'] = 'Invalid id!';
		}
		
		if($form['status'] == 1 && empty($FILES['image']['tmp_name'])){
			$form['status'] = 0;
			$form['message'] = 'Please insert an image!';
		}
		
		if($form['status'] == 1){
			$uploadImage = $this->uploadImage($POST, $FILES);
		}
		
		if($form['status'] == 1 && !isset($uploadImage['image-url'])){
			$form['status'] = 0;
			$form['message'] = $uploadImage['message'];
		}
		
		if($form['status'] == 1){
			$background = explode('/', $uploadImage['image-url']);
			$background = end($background);
			
			$query = $this->db->prepare("UPDATE `posts` SET `post_background` = :post_background WHERE `ID` = :id AND `post_author` = :post_author");
			$query->execute(array(
				'post_background' => $background,
				'id' => $POST['id'],
				'post_author' => $GLOBALS['user']['id']
			));
			
			$form['message'] = 'Done!';
			$form['new-background'] = $uploadImage['image-url'];
		}
		
		return $form;
	}
}
?>