<?php error_reporting(0); ?>
<!DOCTYPE html>
<head>
	<title>Rose installation!</title>
	<style>
		html{ height: 100%; margin: 0 auto; padding: 0; }
		body{ background: url('body-bg.png') repeat; margin: 0; padding: 0; height: 100%; font-family: "Helvetica Neue", helvetica, arial, sans-serif; font-size: 13px; color: #4c5357; text-align: center; }
		#layout{ margin: 0 auto; width: 700px; text-align: left; }
		#layout .logo{ width: 100%; padding: 10px 0 30px 0; }
		#layout .content{ padding: 10px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; background: #ffffff; box-shadow: 0 0 10px #666666; }
		#layout .content .title{ font-weight: bold; font-size: 17px; margin: 0 0 10px 0; }
		#layout .content ul{ list-style: none; margin: 0; padding: 0; }
		#layout .content ul li{ width: 100%; height: 30px; line-height: 30px; padding: 4px 0 4px 0; border-bottom: 1px dashed #f1f1f1; }
		#layout .content ul li:hover{ border-bottom: 1px dashed #cccccc; }
		#layout .content ul li input[type="text"], #layout .content ul li input[type="password"]{ float: right; outline: 0; width: 150px; height: 13px; padding: 5px; color: #666666; border: 1px solid #cccccc; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; }
		#layout .content ul li input[type="text"]:hover, #layout .content ul li input[type="password"]:hover{ border: 1px solid #999999; }
		#layout .content ul li input[type="submit"]{ float: right; }
		#layout .content .alert{ margin: 5px 0 5px 0; padding: 10px; color: #ffffff; -moz-border-radius: 5px; border-radius: 5px; background: #ffffff; }
		#layout .content .alert.error{ background: rgba(255, 0, 0, 0.7); }
		#layout .content .alert.success{ background: rgba(50, 151, 0, 0.7); }
	</style>
</head>
<body>

<?php
$form = array(
	'status' => 1,
	'message' => ''
);

if(isset($_POST['submit-install'])){
	if($form['status'] == 1 && empty($_POST['url'])){
		$form['status'] = 0;
		$form['message'] = 'Please insert an url!';
	}
	
	if($form['status'] == 1 && empty($_POST['host'])){
		$form['status'] = 0;
		$form['message'] = 'Please insert a host!';
	}
	
	if($form['status'] == 1 && empty($_POST['database'])){
		$form['status'] = 0;
		$form['message'] = 'Please insert a database!';
	}
	
	if($form['status'] == 1 && empty($_POST['username'])){
		$form['status'] = 0;
		$form['message'] = 'Please insert an username!';
	}
	
	if($form['status'] == 1 && empty($_POST['password'])){
		$_POST['password'] = '';
	}
	
	if($form['status'] == 1){
		$connect = mysql_connect($_POST['host'], $_POST['username'], $_POST['password']);
		if(!$connect){
			$form['status'] = 0;
			$form['message'] = 'Couldn\'t connect to the database (the host, username or password are invalid)!';
		}
	}
	
	if($form['status'] == 1){
		$database_select = mysql_select_db($_POST['database'], $connect);
		if(!$database_select){
			$form['status'] = 0;
			$form['message'] = 'Couldn\'t select the database!';
		}
	}
	
	if($form['status'] == 1){
		mysql_query("
			CREATE TABLE IF NOT EXISTS `bookmarks` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`post_id` bigint(20) NOT NULL,
				`user_id` bigint(20) NOT NULL,
				`status` bit(1) NOT NULL DEFAULT b'1',
				PRIMARY KEY (`id`),
				UNIQUE KEY `post_id` (`post_id`,`user_id`)
			)
		");
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS `comments` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`comm_author` int(11) NOT NULL,
				`post_id` int(11) NOT NULL,
				`comm_text` text CHARACTER SET latin1 NOT NULL,
				`comm_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)
		");
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS `posts` (
				`ID` int(11) NOT NULL AUTO_INCREMENT,
				`post_author` int(11) NOT NULL,
				`post_date` datetime NOT NULL,
				`post_content` text CHARACTER SET latin1 NOT NULL,
				`post_title` text CHARACTER SET latin1 NOT NULL,
				`post_subtitle` text CHARACTER SET latin1 NOT NULL,
				`post_status` varchar(20) CHARACTER SET latin1 NOT NULL,
				`word_count` int(11) NOT NULL,
				`post_background` char(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'jumbotron.jpeg',
				PRIMARY KEY (`ID`),
				KEY `post_author` (`post_author`)
			)
		");
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS `recommends` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`post_id` bigint(20) NOT NULL,
				`user_id` bigint(20) NOT NULL,
				`status` bit(1) NOT NULL DEFAULT b'1',
				PRIMARY KEY (`id`),
				UNIQUE KEY `post_id` (`post_id`,`user_id`)
			)
		");
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS `reset_password_tokens` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user` int(11) DEFAULT '0',
				`token` varchar(32) DEFAULT NULL,
				`ip` varchar(15) DEFAULT NULL,
				`status` int(1) DEFAULT '0',
				`date` datetime DEFAULT NULL,
				PRIMARY KEY (`id`)
			)
		");
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS `sessions` (
				`sid` char(32) COLLATE utf8_unicode_ci NOT NULL,
				`type` char(3) COLLATE utf8_unicode_ci NOT NULL,
				`data` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
				`expire` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`sid`)
			)
		");
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS `users` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`username` varchar(64) CHARACTER SET latin1 NOT NULL,
				`password` varchar(32) CHARACTER SET latin1 NOT NULL,
				`name` varchar(64) CHARACTER SET latin1 NOT NULL,
				`email` varchar(64) CHARACTER SET latin1 NOT NULL,
				`bio` text CHARACTER SET latin1,
				`reg_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`avatar` char(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'defaultusr.jpeg',
				PRIMARY KEY (`id`)
			)
		");
		
		if(substr($_POST['url'], -1) != '/'){
			$_POST['url'] .= '/';
		}
$config = '<?php
//error_reporting(E_ALL);

$DATABASE = array(\'host\' => \''.$_POST['host'].'\',
				  \'db\'   => \''.$_POST['database'].'\',
				  \'user\' => \''.$_POST['username'].'\',
				  \'pass\' => \''.$_POST['password'].'\');

$GLOBALS[\'siteUrl\'] = \''.$_POST['url'].'\';
?>';
		$fh = fopen('../application/core/config.php', 'w');
			fwrite($fh, $config);
		fclose($fh);
		
		$form['message'] = 'Success! You have installed the script! Please delete the /install/ folder for your security!';
	}
}
?>

<div id="layout">
	<div class="logo">
		<img src="rose-logo.png" alt="Rose" />
	</div>
	<div class="content">
		<div class="title">Installation</div>
		<?php if(!empty($form['message'])){ ?>
			<div class="alert <?php echo (($form['status'] == 0) ? 'error' : 'success'); ?>">
				<?php echo $form['message']; ?>
			</div>
		<?php } // end if ?>
		<?php if(empty($form['message']) || $form['status'] == 0){ ?>
		<form method="post" action="">
			<ul>
				<li>Website url (http://www.domain.tld/) <input type="text" name="url" value="<?php echo (isset($_POST['url']) ? $_POST['url'] : ''); ?>" /></li>
				<li>Host <input type="text" name="host" value="<?php echo (isset($_POST['host']) ? $_POST['host'] : ''); ?>" /></li>
				<li>Database <input type="text" name="database" value="<?php echo (isset($_POST['database']) ? $_POST['database'] : ''); ?>" /></li>
				<li>Username <input type="text" name="username" value="<?php echo (isset($_POST['username']) ? $_POST['username'] : ''); ?>" /></li>
				<li>Password <input type="password" name="password" /></li>
				<li><input type="submit" name="submit-install" value="Install" /></li>
			</ul>
		</form>
		<?php } // end if ?>
	</div>
</div>

</body>
</html>