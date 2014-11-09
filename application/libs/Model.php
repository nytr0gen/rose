<?php
class Model {
	public $db;
	public $URL;

	function __construct() {
		global $URL;
		global $DATABASE;
		
		$this->URL = $URL;

		$DATABASE['dsn'] = sprintf('mysql:host=%s;dbname=%s', $DATABASE['host'], $DATABASE['db']);
		$this->db = new PDO($DATABASE['dsn'], $DATABASE['user'], $DATABASE['pass']);
		$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
		
		$this->session = new Session($this->db);
		$this->defineConstants();
	}

	/**
	 * Defines the constants needed
	 */
	private function defineConstants() {
		if (substr($GLOBALS['siteUrl'], -1) != '/') {
			$GLOBALS['siteUrl'] .= '/';
		}
		define('SITE_URL', $GLOBALS['siteUrl']);

		$GLOBALS['user'] = $this->_getLoggedUser();
		define('LOGGED_IN', (bool) $GLOBALS['user']);
		//define('LOGGED_IN_ADMIN', (isset($_SESSION['admin']['logged']) && $_SESSION['admin']['logged'])); 
	
		$GLOBALS['msg'] = $this->_getMsg();

		define('WORDS_PER_MINUTE',  240);
		define('POSTS_PER_PAGE',    8);
		define('COMMENTS_PER_PAGE', 6);

		define('IMG_UPLOAD_PATH', 'uploads/');
		define('IMG_ALLOWED_EXT', 'jpg,jpeg,gif,png,bmp');
		define('IMG_MAX_SIZE'   , 8388608);
	}

	/** 
	 * Generate unique string
	 * @param  int $len Length of the uniq string
	 * @return string      Generated string
	 */
	public function uniq($len) {
		$chars    = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$charsLen = strlen($chars)-1;

		$ret = '';
		for ($i = 0; $i < $len; ++$i) {
		    $ret .= $chars[mt_rand(0, $charsLen)];
		}

		return $ret;
	}

	/**
	 * Gets the message intented to be shown to the user
	 * @return string The message from session
	 */
	private function _getMsg() {
		$msg = '';
		if (isset($_SESSION['msg'])) {
			$msg = $_SESSION['msg'];
			unset($_SESSION['msg']);
		}
		
		return $msg;
	}
	
	/**
	 * Puts a message to be shown to the user on the next page
	 * @param  string $msg 
	 */
	public function putMsg($msg) {
		$_SESSION['msg'] = $msg;
	}
	
	/**
	 * hash $pass
	 */
	public function hashPassword($pass) {
		$pass = md5($pass) . $pass;
		$pass = md5('sarpe' . $pass);

		return $pass;
	}
	
	/**
	 * clean form post data (array)
	 */
	public function cleanPostArray($array){
		$cleanArray = array();
		
		foreach($array as $key => $value){
			$cleanArray[$key] = $this->cleanPostValue($value);
		}
		
		return $cleanArray;
	}
	
	/**
	 * clean form post data (value)
	 */
	public function cleanPostValue($value){ 
		// o mica protectie pt a afisa corect ceea ce insereaza.
		// sunt diferiti factori care pot fute si asta impiedica
			// $value = htmlspecialchars_decode($value);
		
		
		$value = trim($value);
		$value = htmlspecialchars($value);
		
		return $value;
	}

	/**
	 * Redirects to a specified local link
	 */
	public function redirect($link = '') {
		header('Location: ' . SITE_URL . $link);
	}

	/**
	 * Gets logged user details
	 * Used in defineConstants and added to GLOBALS['user']
	 * @return [type] [description]
	 */
	private function _getLoggedUser() {
		if (!isset($_COOKIE['SID'])) {
            return false;
        }

        $sid = $this->session->get($_COOKIE['SID']);
        if (  !$sid
            || $sid['type'] != 'SID'
        ) { return false; }

        $ret = $this->getUserByUserId($sid['data']);
        return $ret;
	}

	/**
	 * Gets user data from db by the user id
	 * @param  int $userId
	 * @return array         Db rows
	 */
	public function getUserByUserId($userId) {
		$sth = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    	$sth->execute(array($userId));

        return $sth->fetch();
	}

    /**
     * Gets a post list by user id
     * @param  int $user_id     The id of the user
     * @param  string $post_status Post status, for example draft
     * @return array              Posts
     */
    public function getPostsByUserId($userId, $postStatus, $offset = 0) {
    	$offset   = $offset * POSTS_PER_PAGE;
    	$rowCount = POSTS_PER_PAGE;

    	$sql = '';
    	if ($userId != -1) {
    		$sql = 'u.id = :userId AND ';
    	}
        $sql = 'SELECT p.ID as ID, post_title, post_subtitle,
                    username, name, word_count, b.status as bookmark_status,
                    UNIX_TIMESTAMP(post_date) as post_date
                FROM posts p JOIN users u ON (post_author=u.id) 
               		LEFT JOIN bookmarks b ON (b.post_id=p.ID AND b.user_id=:loggedUserId)
                WHERE ' . $sql . 'post_status = :postStatus
                ORDER BY post_date DESC LIMIT :offset, :rowCount';
        $sth = $this->db->prepare($sql);
        $sth->bindValue(':loggedUserId', $GLOBALS['user']['id'], PDO::PARAM_INT);
        if ($userId != -1) {
       	 	$sth->bindValue(':userId',   $userId,                PDO::PARAM_INT);
       	}
        $sth->bindValue(':postStatus',   $postStatus,            PDO::PARAM_STR);
        $sth->bindValue(':offset',       $offset,                PDO::PARAM_INT);
        $sth->bindValue(':rowCount',     $rowCount,              PDO::PARAM_INT);
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
     * Checks the existence of an user
     * @param  string $username
     * @return bool          
     */
    public function userExists($username) {
        $sth = $this->db->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
        $sth->execute(array($username));

        return (bool) $sth->fetch();
    }
	
	private function _getExtension($file) {
        return strtolower(substr($file, strrpos($file, '.')+1));
    }
	
	/**
	 * Uploads image
	 * @param  array $POST 
	 * @param  array $FILES 
	 * @return array        Upload status
	 */
	public function uploadImage($POST, $FILES){
		$data = array('status' => 1);

		/**
		 * Checks for size overflow as stated in the link below
		 * http://andrewcurioso.com/2010/06/detecting-file-size-overflow-in-php/
		 */
		if (   $_SERVER['REQUEST_METHOD'] == 'POST' 
			&& $_SERVER['CONTENT_LENGTH'] > 0 
			&& empty($_POST) 
			&& empty($_FILES) 
		) {       
			$data['message'] = 'Your file is too large';
			$data['status']  = 0;

			return $data;
		}

		// Checks the existence of a file
        if (   $_FILES['image']['size'] == 0
            || $_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
            $data['message'] = 'You must select a file';
        	$data['status']  = 0;

            return $data;
        }

        // Checks the size of the uploaded file
        if (   $_FILES['image']['size'] > IMG_MAX_SIZE
            || $_FILES['image']['error'] == UPLOAD_ERR_INI_SIZE) {
            $data['message'] = 'Your file is too large';
        	$data['status']  = 0;

            return $data;
        }

        // Check if the extension is allowed
        $ext = $this->_getExtension($_FILES['image']['name']);
        if (strpos(IMG_ALLOWED_EXT, $ext) === false) {
            $data['message'] = 'Your file type is not allowed';
            $data['status']  = 0;

            return $data;
        }

        // Generates an unique name for the file
        do { 
        	$file = $this->uniq(10);
        } while (file_exists(IMG_UPLOAD_PATH . $file . '.' . $ext));
        $file = $file . '.' . $ext;

        // Makes the directory available
        if (!is_dir(IMG_UPLOAD_PATH)) {
            mkdir(IMG_UPLOAD_PATH, 0777, true);
        }

        // Uploads file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], (IMG_UPLOAD_PATH . $file))) {
            $data['message'] = 'Something strange happened, try again later ' .
            				   'or if the problem persists, contact the admin';
            $data['status']  = 0;

            return;
        }

        $data['image-url'] = SITE_URL . IMG_UPLOAD_PATH . $file;

		return $data;
	}
}
?>