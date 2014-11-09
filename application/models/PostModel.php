<?php
class PostModel extends Model {
    public function getPostById($ID) {
        $sql = 'SELECT p.ID as ID, post_title, post_subtitle, post_content, post_status, post_background,
        			UNIX_TIMESTAMP(post_date) as post_date, post_author,
        			username, name
        		FROM posts p JOIN users u ON (post_author=u.id)
                WHERE p.ID = ? LIMIT 1';
        $sth = $this->db->prepare($sql);
        $sth->execute(array($ID));

        $row = $sth->fetch();
        $row['post_date_supertag'] = date('j<b\r>M', $row['post_date']);

        return $row;
    }
	
	public function getRecommendsByPostId($postId){
		$query = $this->db->prepare("
			SELECT `users`.*, `recommends`.`status` FROM `recommends`
			RIGHT JOIN `users` ON `recommends`.`user_id` = `users`.`id`
			WHERE `recommends`.`post_id` = :post_id AND `recommends`.`user_id` = :user_id
			LIMIT 1
		");
		$query->execute(array(
			'post_id' => $postId,
			'user_id' => $GLOBALS['user']['id']
		));
		$row = $query->fetch();
		
		return $row;
	}
	
	public function getRecommendsByPostIdAndLimit($postId, $limit){
		$query = $this->db->prepare("
			SELECT `users`.* FROM `recommends`
			RIGHT JOIN `users` ON `recommends`.`user_id` = `users`.`id`
			WHERE `recommends`.`post_id` = :post_id AND `recommends`.`status` = (:status)
			LIMIT :limit
		");
		$query->bindValue(':post_id', $postId, PDO::PARAM_INT);
		$query->bindValue(':status', 1, PDO::PARAM_INT);
		$query->bindValue(':limit', $limit, PDO::PARAM_INT);
		$query->execute();
		$rows = $query->fetchAll();
		
		return $rows;
	}
}
?>